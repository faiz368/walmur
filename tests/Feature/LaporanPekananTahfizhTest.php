<?php

namespace Tests\Feature;

use App\Models\AbsensiHalaqoh;
use App\Models\Halaqah;
use App\Models\LaporanPekananKelas;
use App\Models\LaporanPekananKelasDetail;
use App\Models\Santri;
use App\Models\Setoran;
use App\Models\User;
use App\Support\LaporanPekananData;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Query tab Tahfizh memakai skema minimal inline karena tabel `santris`,
 * `halaqahs`, `setorans`, dan `absensis` dimiliki StudentHub.
 */
class LaporanPekananTahfizhTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kelas')->nullable();
            $table->timestamps();
        });

        Schema::create('halaqahs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_halaqah');
            $table->unsignedBigInteger('ustadz_id')->nullable();
            $table->text('deskripsi')->nullable();
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });

        Schema::create('santris', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('nis')->nullable();
            $table->string('nama_lengkap');
            $table->string('kelas')->nullable();
            $table->unsignedBigInteger('kelas_id')->nullable();
            $table->unsignedBigInteger('halaqah_id')->nullable();
            $table->string('halaqoh')->nullable();
            $table->string('target_juz')->nullable();
            $table->string('current_juz')->nullable();
            $table->string('total_surah_hafal')->nullable();
            $table->timestamps();
        });

        Schema::create('setorans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('santri_id');
            $table->unsignedBigInteger('ustadz_id')->nullable();
            $table->string('jenis_setoran');
            $table->string('juz');
            $table->string('surah_name');
            $table->integer('ayat_awal');
            $table->integer('ayat_akhir');
            $table->string('nilai');
            $table->decimal('nilai_angka', 5, 2)->nullable();
            $table->string('nilai_predikat')->nullable();
            $table->string('absensi');
            $table->text('catatan')->nullable();
            $table->date('tanggal');
            $table->string('tahun_ajaran');
            $table->string('semester');
            $table->string('kategori_murajaah')->nullable();
            $table->string('porsi_murajaah')->nullable();
            $table->integer('jumlah_halaman_murajaah')->nullable();
            $table->timestamps();
        });

        Schema::create('absensis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('santri_id');
            $table->unsignedBigInteger('ustadz_id')->nullable();
            $table->date('tanggal');
            $table->string('status');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        $migration = require database_path('migrations/2026_10_23_000001_create_laporan_pekanans_tables.php');
        $migration->up();
    }

    private function user(string $nama = 'Ustadz Pembimbing'): User
    {
        return User::query()->forceCreate([
            'name' => $nama,
            'email' => strtolower(str_replace(' ', '.', $nama)).'@example.test',
        ]);
    }

    private function halaqah(User $ustadz, string $nama = 'Halaqah Imam Mahfuzh'): Halaqah
    {
        return Halaqah::query()->forceCreate([
            'nama_halaqah' => $nama,
            'ustadz_id' => $ustadz->id,
            'urutan' => 1,
        ]);
    }

    private function santri(string $nama, array $attributes = []): Santri
    {
        return Santri::query()->forceCreate(array_merge([
            'nama_lengkap' => $nama,
            'nis' => 'NIS-'.$nama,
            'kelas' => 'Kelas VIII',
            'target_juz' => '30',
            'current_juz' => '3.25',
            'total_surah_hafal' => '12',
        ], $attributes));
    }

    private function header(array $attributes = []): LaporanPekananKelas
    {
        return LaporanPekananKelas::query()->forceCreate(array_merge([
            'kelas_id' => null,
            'kelas_nama' => 'Kelas VIII',
            'periode_mulai' => '2026-08-30',
            'periode_selesai' => '2026-09-05',
            'status' => 'published',
            'published_at' => '2026-09-05 10:00:00',
            'created_by' => null,
        ], $attributes));
    }

    private function detail(LaporanPekananKelas $header, Santri $santri, array $attributes = []): LaporanPekananKelasDetail
    {
        return LaporanPekananKelasDetail::query()->forceCreate(array_merge([
            'laporan_pekanan_id' => $header->id,
            'santri_id' => $santri->id,
            'nama_santri' => $santri->nama_lengkap,
            'nis' => $santri->nis,
            'grade' => 'Grade D',
            'capaian_pekanan' => 1.5,
            'target_pekanan' => 2.0,
            'persentase' => 75,
            'total_hafalan_juz' => 3.25,
            'total_hafalan_halaman' => 10,
            'absensi_hadir' => 4,
            'absensi_sakit' => 1,
            'absensi_izin' => 0,
            'absensi_alpa' => 0,
            'absensi_tidak_setor' => 1,
            'catatan' => 'Lancar',
            'urutan' => 1,
        ], $attributes));
    }

    private function setoran(Santri $santri, array $attributes = []): Setoran
    {
        return Setoran::query()->forceCreate(array_merge([
            'santri_id' => $santri->id,
            'ustadz_id' => null,
            'jenis_setoran' => 'Ziyadah',
            'juz' => '30',
            'surah_name' => 'An-Naba',
            'ayat_awal' => 1,
            'ayat_akhir' => 10,
            'nilai' => '100',
            'nilai_angka' => 100,
            'nilai_predikat' => 'Mumtaz',
            'absensi' => 'Hadir',
            'catatan' => null,
            'tanggal' => '2026-09-02',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'Ganjil',
            'kategori_murajaah' => null,
            'porsi_murajaah' => null,
            'jumlah_halaman_murajaah' => null,
        ], $attributes));
    }

    private function absensi(Santri $santri, string $status, string $tanggal = '2026-09-02'): AbsensiHalaqoh
    {
        return AbsensiHalaqoh::query()->forceCreate([
            'santri_id' => $santri->id,
            'ustadz_id' => null,
            'tanggal' => $tanggal,
            'status' => $status,
            'catatan' => null,
        ]);
    }

    public function test_hanya_detail_santri_sendiri_yang_dimuat(): void
    {
        $santri = $this->santri('Anak Sendiri');
        $santriLain = $this->santri('Anak Lain');

        $header = $this->header();
        $this->detail($header, $santri, ['target_pekanan' => 1.5]);
        $this->detail($header, $santriLain, ['target_pekanan' => 9.9]);

        $hasil = LaporanPekananData::untuk($santri)->laporanTahfizh(
            '2026-09-01', '2026-09-30', '1', 'September', null
        );

        $this->assertCount(1, $hasil);
        $this->assertCount(1, $hasil->first()->details);
        $this->assertSame($santri->id, $hasil->first()->details->first()->santri_id);
        $this->assertSame(1.5, $hasil->first()->details->first()->target_pekanan);
    }

    public function test_hanya_laporan_published_yang_dimuat(): void
    {
        $santri = $this->santri('Anak Sendiri');

        $draft = $this->header(['status' => 'draft', 'published_at' => null]);
        $published = $this->header([
            'periode_mulai' => '2026-09-06',
            'periode_selesai' => '2026-09-12',
        ]);

        $this->detail($draft, $santri);
        $this->detail($published, $santri);

        $hasil = LaporanPekananData::untuk($santri)->laporanTahfizh(
            '2026-09-01', '2026-09-30', '1', 'September', null
        );

        $this->assertSame([$published->id], $hasil->pluck('id')->all());
    }

    public function test_detail_tanpa_santri_id_dicocokkan_lewat_nis(): void
    {
        $santri = $this->santri('Anak Sendiri');
        $header = $this->header();

        $this->detail($header, $santri, [
            'santri_id' => null,
            'nis' => $santri->nis,
        ]);

        $hasil = LaporanPekananData::untuk($santri)->laporanTahfizh(
            '2026-09-01', '2026-09-30', '1', 'September', null
        );

        $this->assertCount(1, $hasil);
        $this->assertNull($hasil->first()->details->first()->santri_id);
        $this->assertSame($santri->nis, $hasil->first()->details->first()->nis);
    }

    public function test_filter_periode_tidak_lagi_memakai_kelas_santri(): void
    {
        $santri = $this->santri('Anak Sendiri', ['kelas' => 'Kelas VIII']);

        $september = $this->header(['kelas_nama' => 'Kelas IX']);
        $oktober = $this->header([
            'periode_mulai' => '2026-10-04',
            'periode_selesai' => '2026-10-10',
        ]);
        $semesterDua = $this->header([
            'periode_mulai' => '2027-01-31',
            'periode_selesai' => '2027-02-06',
        ]);

        foreach ([$september, $oktober, $semesterDua] as $header) {
            $this->detail($header, $santri);
        }

        $hasil = LaporanPekananData::untuk($santri)->laporanTahfizh(
            '2026-09-01', '2026-09-30', '1', 'September', null
        );

        $this->assertSame([$september->id], $hasil->pluck('id')->all());
    }

    public function test_ringkasan_tahfizh_memakai_laporan_setoran_dan_presensi(): void
    {
        $ustadz = $this->user('Ustadz Fulan');
        $halaqah = $this->halaqah($ustadz, 'Halaqah Zaid Bin Tsabit');
        $santri = $this->santri('Anak Sendiri', ['halaqah_id' => $halaqah->id]);

        $header = $this->header();
        $this->detail($header, $santri);

        $this->setoran($santri);
        $this->setoran($santri, [
            'jenis_setoran' => 'Murajaah',
            'surah_name' => 'Juz 30',
            'kategori_murajaah' => 'lancar',
            'jumlah_halaman_murajaah' => 20,
            'tanggal' => '2026-09-03',
        ]);
        $this->setoran($santri, [
            'jenis_setoran' => 'Murajaah30Juz',
            'surah_name' => 'Juz 29',
            'kategori_murajaah' => 'kurang_lancar',
            'jumlah_halaman_murajaah' => 5,
            'tanggal' => '2026-09-04',
        ]);

        $this->absensi($santri, 'Hadir', '2026-09-02');
        $this->absensi($santri, 'Sakit', '2026-09-03');

        $laporan = LaporanPekananData::untuk($santri)->laporanTahfizh(
            '2026-09-01', '2026-09-30', '1', 'September', null
        );
        $ringkasan = LaporanPekananData::untuk($santri)->ringkasanTahfizh(
            '2026-09-01', '2026-09-30', $laporan
        );

        $this->assertSame('Ustadz Fulan', $ringkasan['guru']['nama']);
        $this->assertSame('Halaqah Zaid Bin Tsabit', $ringkasan['guru']['halaqah']);
        $this->assertSame(1.5, $ringkasan['capaian']['capaian']);
        $this->assertSame(2.0, $ringkasan['capaian']['target']);
        $this->assertSame(75.0, $ringkasan['capaian']['persentase']);
        $this->assertSame(1, $ringkasan['capaian']['jumlah_ziyadah']);
        $this->assertSame(2, $ringkasan['murojaah']['jumlah']);
        $this->assertSame(25.0, $ringkasan['murojaah']['halaman']);
        $this->assertSame([
            'Hadir' => 4,
            'Sakit' => 1,
            'Izin' => 0,
            'Alfa' => 0,
            'Tidak Setor' => 1,
        ], $ringkasan['presensi']['rekap']);
        $this->assertCount(2, $ringkasan['presensi']['items']);
        $this->assertSame(3.25, $ringkasan['hafalan']['juz']);
        $this->assertSame(10.0, $ringkasan['hafalan']['halaman']);
    }

    public function test_ringkasan_fallback_ke_setoran_dan_absensi_tanpa_laporan(): void
    {
        $santri = $this->santri('Anak Sendiri', [
            'halaqoh' => 'Halaqoh tanpa relasi',
            'current_juz' => '4.50',
            'total_surah_hafal' => '15',
        ]);

        $this->setoran($santri);
        $this->setoran($santri, [
            'jenis_setoran' => 'Murajaah',
            'jumlah_halaman_murajaah' => 3,
            'tanggal' => '2026-09-03',
        ]);

        $this->absensi($santri, 'Hadir');
        $this->absensi($santri, 'Alpa', '2026-09-03');
        $this->absensi($santri, 'Alpha', '2026-09-04');
        $this->absensi($santri, 'Tidak Setor', '2026-09-05');

        $ringkasan = LaporanPekananData::untuk($santri)->ringkasanTahfizh(
            '2026-09-01', '2026-09-30', collect()
        );

        $this->assertNull($ringkasan['guru']['nama']);
        $this->assertSame('Halaqoh tanpa relasi', $ringkasan['guru']['halaqah']);
        $this->assertNull($ringkasan['capaian']['capaian']);
        $this->assertSame(1, $ringkasan['capaian']['jumlah_ziyadah']);
        $this->assertSame('An-Naba', $ringkasan['capaian']['ziyadah_terakhir']->surah_name);
        $this->assertSame(1, $ringkasan['murojaah']['jumlah']);
        $this->assertSame(3.0, $ringkasan['murojaah']['halaman']);
        $this->assertSame([
            'Hadir' => 1,
            'Sakit' => 0,
            'Izin' => 0,
            'Alfa' => 2,
            'Tidak Setor' => 1,
        ], $ringkasan['presensi']['rekap']);
        $this->assertSame(4.5, $ringkasan['hafalan']['juz']);
        $this->assertSame(0.0, $ringkasan['hafalan']['halaman']);
        $this->assertSame(15, $ringkasan['hafalan']['total_surah']);
    }
}
