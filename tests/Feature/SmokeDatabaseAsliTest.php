<?php

namespace Tests\Feature;

use App\Filament\Wali\Pages\Auth\Login;
use App\Filament\Wali\Pages\Dashboard;
use App\Filament\Wali\Pages\LaporanPekanan;
use App\Filament\Wali\Pages\ProfilTidakDitemukan;
use App\Models\GuruMapelSetting;
use App\Models\Kelas;
use App\Models\NilaiSumatif;
use App\Models\Santri;
use App\Models\User;
use App\Support\RaporBayanganData;
use App\Support\Terbilang;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Uji tampilan terhadap database StudentHub sungguhan.
 *
 * Sengaja tidak ikut jalan di `php artisan test` biasa: WALMUR tidak memiliki
 * migration tabel `users`/`santris` (tabel itu milik aplikasi lain), jadi test
 * ini hanya bisa jalan kalau diarahkan ke database bersama.
 *
 * Jalankan:
 *   DB_CONNECTION=mysql php artisan test --filter=SmokeDatabaseAsliTest
 *
 * Test ini READ-ONLY. Satu-satunya tulisan yang boleh terjadi adalah baris
 * audit di `wali_access_logs` yang memang dibuat WALMUR.
 */
class SmokeDatabaseAsliTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Butuh DB_CONNECTION=mysql (database StudentHub).');
        }
    }

    private function masukSebagai(int $userId): User
    {
        $user = User::query()->findOrFail($userId);
        $this->actingAs($user);

        return $user;
    }

    private function assertTampil(string $html, string ...$potongan): void
    {
        foreach ($potongan as $p) {
            $this->assertStringContainsString($p, $html, "Tidak ditemukan: {$p}");
        }
    }

    public function test_login_guru_ditolak_dan_tercatat_di_audit(): void
    {
        $guru = User::query()->where('role', 'guru')->firstOrFail();

        $sebelum = DB::table('wali_access_logs')->count();

        Livewire::test(Login::class)
            ->fillForm(['email' => $guru->email])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();

        $baris = DB::table('wali_access_logs')->orderByDesc('id')->first();
        $this->assertFalse((bool) $baris->berhasil);
        $this->assertSame($sebelum + 1, DB::table('wali_access_logs')->count());
    }

    public function test_dashboard_santri_dengan_kbm_menampilkan_nilai(): void
    {
        // user 7 -> Santri 130 (Kelas VIII, punya 3 baris laporan pekanan).
        $this->masukSebagai(7);

        $santri = User::query()->findOrFail(7)->getSantriProfile();
        $this->assertNotNull($santri);
        $this->assertSame(130, $santri->id);

        $html = Livewire::test(Dashboard::class)->assertOk()->html();

        $this->assertTampil(
            $html,
            $santri->nama_lengkap,
            $santri->nis,
            'Rekapitulasi Kehadiran',
            'Wali Kelas',
            'Walas, S.Kom',
            'Total Hadir',
            'Total Alfa',
        );

        // Dashboard sengaja tidak Listing nilai per mapel; itu isi halaman laporan.
        $this->assertSame(3, Livewire::test(Dashboard::class)->instance()->getViewData()['jumlahKbm']);
    }

    public function test_dashboard_santri_tanpa_kbm_tidak_error(): void
    {
        // user 130 -> Santri 2 (Kelas XII, belum ada laporan pekanan).
        $this->masukSebagai(130);

        $santri = User::query()->findOrFail(130)->getSantriProfile();
        $this->assertSame(2, $santri->id);

        $html = Livewire::test(Dashboard::class)->assertOk()->html();

        $this->assertTampil($html, $santri->nama_lengkap, 'Total Hadir');
        $this->assertStringNotContainsString('Information Technology', $html);
    }

    public function test_laporan_pekanan_semua_tab_tampil(): void
    {
        $this->masukSebagai(7);

        $santri = User::query()->findOrFail(7)->getSantriProfile();

        $kbm = Livewire::test(LaporanPekanan::class)->assertOk();
        $this->assertTampil($kbm->html(), 'Aqidah', '100');

        $kesiswaan = Livewire::test(LaporanPekanan::class)->set('tabAktif', 'kesiswaan')->assertOk();
        $this->assertTampil(
            $kesiswaan->html(),
            'Rekapitulasi Kehadiran',
            'Total Alfa',
            'bebas dari pelanggaran',
        );

        $tahfizh = Livewire::test(LaporanPekanan::class)->set('tabAktif', 'tahfizh')->assertOk();
        $this->assertTampil($tahfizh->html(), 'Menyusul', 'Capaian Tahfizh');
    }

    public function test_filter_bulan_september_menampilkan_absensi(): void
    {
        // Santri 2 punya 41 baris absensi 20 Jul - 11 Sep 2026.
        $this->masukSebagai(130);

        $html = Livewire::test(LaporanPekanan::class)
            ->set('bulan', 'September')
            ->set('tabAktif', 'kesiswaan')
            ->assertOk()
            ->html();

        $this->assertTampil($html, 'Total Hadir', 'Hadir', 'Rakha Fayyaz Gunawan');
        $this->assertStringContainsString('10', $html);
    }

    public function test_filter_bulan_dan_pekan_mempersempit_periode(): void
    {
        $this->masukSebagai(7);

        $komponen = Livewire::test(LaporanPekanan::class)->set('bulan', 'September');

        $periode = $komponen->instance()->getViewData()['periode'];
        $this->assertSame('2026-09-01', $periode['dari']);
        $this->assertSame('2026-09-30', $periode['sampai']);

        $minggu = Livewire::test(LaporanPekanan::class)
            ->set('bulan', 'September')
            ->set('pekan', '1');

        $periode = $minggu->instance()->getViewData()['periode'];
        $this->assertSame('2026-08-30', $periode['dari']);
        $this->assertSame('2026-09-05', $periode['sampai']);
    }

    public function test_akun_tanpa_profil_diarahkan_ke_layar_pengingat(): void
    {
        // user 33 -> tidak tertaut ke santris mana pun.
        $user = $this->masukSebagai(33);
        $this->assertNull($user->getSantriProfile());

        Livewire::test(Dashboard::class)->assertRedirect(ProfilTidakDitemukan::getUrl());
    }

    public function test_halaman_laporan_tidak_membocorkan_data_santri_lain(): void
    {
        $this->masukSebagai(7);

        $html = Livewire::test(LaporanPekanan::class)->assertOk()->html();

        // Hanya nama anak sendiri yang boleh muncul.
        $namaLain = Santri::query()
            ->where('kelas', 'Kelas VIII')
            ->where('id', '!=', 130)
            ->value('nama_lengkap');

        if ($namaLain) {
            $this->assertStringNotContainsString($namaLain, $html);
        }
    }

    public function test_tab_rapor_bayangan_menampilkan_dokumen_sts(): void
    {
        $this->masukSebagai(7);

        $santri = User::query()->findOrFail(7)->getSantriProfile();

        $rapor = Livewire::test(LaporanPekanan::class)->set('tabAktif', 'rapor')->assertOk();

        $this->assertTampil(
            $rapor->html(),
            'Laporan Hasil Evaluasi Formatif dan Sumatif Tengah Semester (STS)',
            'Nilai Angka',
            'Ketidakhadiran',
            'Kepribadian',
            'Catatan Kepribadian Siswa',
            'Kepala Sekolah',
            'Orang Tua / Wali Santri',
        );

        // Santri 130 berrieseed di Kelas VIII, jadi harus memakai kop SMP.
        $this->assertSame('SMP', Kelas::detectTingkat($santri->kelas));
        $this->assertStringContainsString('Kop SMP.png', $rapor->html());

        // Tampilan tab rapor tidak boleh ikut membawa kerangka Laporan Pekanan.
        $this->assertStringContainsString('wali-rapor-sheet', $rapor->html());
    }

    public function test_rapor_menampilkan_nilai_sts_yang_ada_di_database(): void
    {
        $this->masukSebagai(7);

        $santri = User::query()->findOrFail(7)->getSantriProfile();
        $rapor = RaporBayanganData::untuk($santri)->untukSemester('1');

        $html = Livewire::test(LaporanPekanan::class)->set('tabAktif', 'rapor')->assertOk()->html();

        $this->assertNotEmpty($rapor['mapelSettings'], 'Kelas VIII harus punya mapel semester 1.');
        $this->assertNotNull($rapor['tahun']);

        foreach ($rapor['mapelSettings'] as $setting) {
            $this->assertStringContainsString($setting->mata_pelajaran, $html);

            $nilai = $rapor['nilaiPerMapel'][$setting->id] ?? null;

            if ($nilai === null) {
                continue;
            }

            // NILAI ANGKA dibulatkan ke satuan bulat, sama seperti StudentHub.
            $this->assertStringContainsString(number_format($nilai, 0), $html);
            $this->assertStringContainsString(Terbilang::dari($nilai), $html);
        }
    }

    public function test_rapor_hanya_mengambil_nilai_santri_yang_login(): void
    {
        $this->masukSebagai(7);

        $santri = User::query()->findOrFail(7)->getSantriProfile();
        $rapor = RaporBayanganData::untuk($santri)->untukSemester('1');

        $html = Livewire::test(LaporanPekanan::class)->set('tabAktif', 'rapor')->assertOk()->html();

        // Nilaiisanatrir lain di mapel yang sama tidak boleh bocor.
        $settingIds = $rapor['mapelSettings']->pluck('id');

        if ($settingIds->isEmpty()) {
            $this->markTestSkipped('Tidak ada mapel untuk dibandingkan.');
        }

        $nilaiSantriLain = NilaiSumatif::query()
            ->whereIn('setting_id', $settingIds)
            ->where('santri_id', '!=', $santri->id)
            ->whereNotNull('nilai_sts')
            ->pluck('nilai_sts')
            ->map(fn ($n) => number_format($n, 0))
            ->reject(fn ($n) => in_array($n, collect($rapor['nilaiPerMapel'])->map(fn ($x) => $x === null ? null : number_format($x, 0))->all(), true))
            ->unique()
            ->values();

        foreach ($nilaiSantriLain as $nilai) {
            $this->assertStringNotContainsString('>'.$nilai.'<', $html);
        }
    }

    public function test_semester_rapor_terpisah_dari_semester_laporan_pekanan(): void
    {
        $this->masukSebagai(7);

        $komponen = Livewire::test(LaporanPekanan::class)
            ->set('semester', '1')
            ->set('semesterRapor', '2')
            ->set('tabAktif', 'rapor')
            ->assertOk();

        // Mengganti semester rapor tidak boleh mengubah filter Laporan Pekanan.
        $this->assertSame('1', $komponen->instance()->semester);
        $this->assertSame('2', $komponen->instance()->semesterRapor);

        $data = $komponen->instance()->getViewData();
        $this->assertSame('2', $data['rapor']['semester']);
        $this->assertStringContainsString('2 (Genap)', $komponen->html());

        // Nilai semester 2 harus memang terambil dari database yang sama.
        $perkiraan = RaporBayanganData::untuk($data['santri'])->untukSemester('2');
        $this->assertSame(
            $perkiraan['mapelSettings']->pluck('id')->all(),
            $data['rapor']['mapelSettings']->pluck('id')->all(),
        );
    }

    public function test_tahun_ajaran_rapor_adalah_tahun_yang_punya_mapel(): void
    {
        $this->masukSebagai(7);

        $santri = User::query()->findOrFail(7)->getSantriProfile();
        $rapor = RaporBayanganData::untuk($santri)->untukSemester('1');

        $tahunTersedia = GuruMapelSetting::query()
            ->where('kelas', $santri->kelas)
            ->whereNotNull('tahun_ajaran')
            ->distinct()
            ->pluck('tahun_ajaran')
            ->map(fn ($t) => (string) $t)
            ->sort()
            ->values();

        if ($tahunTersedia->isEmpty()) {
            $this->markTestSkipped('Kelas ini belum punya mapel.');
        }

        // Rapor tidak boleh jatuh ke tahun ajaran yang kosong mapelnya.
        $this->assertContains($rapor['tahun'], $tahunTersedia->all());
    }

    public function test_setting_rapor_tidak_pernah_ditulis_oleh_portal(): void
    {
        $sebelum = DB::table('setting_rapors')->count();

        $this->masukSebagai(7);

        $santri = User::query()->findOrFail(7)->getSantriProfile();
        RaporBayanganData::untuk($santri)->untukSemester('2');

        Livewire::test(LaporanPekanan::class)->set('tabAktif', 'rapor')->assertOk();

        // StudentHub memakai firstOrCreate(); WALMUR harus tetap read-only.
        $this->assertSame($sebelum, DB::table('setting_rapors')->count());
    }
}
