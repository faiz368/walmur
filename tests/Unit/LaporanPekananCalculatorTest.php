<?php

namespace Tests\Unit;

use App\Support\LaporanPekananCalculator;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Logika dihitung ulang (bukan disalin) dari StudentHub/app/Models/LaporanPekanan.php.
 * Anchor tahun ajaran 2026/2027: 1 Juli 2026 (Rabu) dimundurkan ke
 * 28 Juni 2026 (Minggu), jadi blok pertama = 28 Juni - 4 Juli 2026.
 */
class LaporanPekananCalculatorTest extends TestCase
{
    private const TA = '2026/2027';

    public function test_anchor_tahun_ajaran_dmundurkan_ke_hari_minggu(): void
    {
        $awal = LaporanPekananCalculator::awalTahunAjaran(self::TA);

        $this->assertSame('2026-06-28', $awal->toDateString());
        $this->assertSame(Carbon::SUNDAY, $awal->dayOfWeek);
    }

    public function test_anchor_tahun_ajaran_mengambil_empat_digit_pertama(): void
    {
        $this->assertSame(
            '2025-06-29',
            LaporanPekananCalculator::awalTahunAjaran('2025/2026')->toDateString()
        );
    }

    public function test_blok_pekan_selalu_minggu_sampai_sabtu(): void
    {
        $hasil = LaporanPekananCalculator::normalisasiPekan('2026-09-16', self::TA);

        $this->assertSame(Carbon::SUNDAY, $hasil['mulai']->dayOfWeek);
        $this->assertSame(Carbon::SATURDAY, $hasil['selesai']->dayOfWeek);
        $this->assertSame('2026-09-13', $hasil['mulai']->toDateString());
        $this->assertSame('2026-09-19', $hasil['selesai']->toDateString());
    }

    public function test_hari_di_luar_anchor_dijepit_ke_minggu_ke_satu(): void
    {
        $hasil = LaporanPekananCalculator::normalisasiPekan('2026-01-01', self::TA);

        $this->assertSame(1, $hasil['minggu_ke']);
        $this->assertSame('2026-06-28', $hasil['mulai']->toDateString());
    }

    /**
     * Nomor bulan/pekan diambil dari Sabtu PENUTUP, karena laporan pekanan
     * rilis setiap Sabtu. Sabtu September 2026 ada tanggal 5, 12, 19, 26, jadi
     * blok 13-19 September = "September / Pekan 3" dan blok 20-26 September
     * = "September / Pekan 4".
     */
    public function test_nomor_pekan_diambil_dari_sabtu_penutup(): void
    {
        $pekanTiga = LaporanPekananCalculator::normalisasiPekan('2026-09-13', self::TA);
        $this->assertSame(9, $pekanTiga['bulan_ke']);
        $this->assertSame(3, $pekanTiga['pekan_dalam_bulan']);

        // Hari Minggu sudah masuk blok berikutnya.
        $pekanEmpat = LaporanPekananCalculator::normalisasiPekan('2026-09-20', self::TA);
        $this->assertSame(9, $pekanEmpat['bulan_ke']);
        $this->assertSame(4, $pekanEmpat['pekan_dalam_bulan']);
    }

    #[DataProvider('urutanSabtu')]
    public function test_urutan_sabtu_dalam_bulan(string $tanggal, int $harapan): void
    {
        $this->assertSame($harapan, LaporanPekananCalculator::urutanSabtu($tanggal));
    }

    public static function urutanSabtu(): array
    {
        return [
            ['2026-09-05', 1],
            ['2026-09-12', 2],
            ['2026-09-19', 3],
            ['2026-09-26', 4],
            // Oktober 2026 punya 5 Sabtu.
            ['2026-10-03', 1],
            ['2026-10-10', 2],
            ['2026-10-17', 3],
            ['2026-10-24', 4],
            ['2026-10-31', 5],
        ];
    }

    public function test_inverse_bulan_pekan_kembali_menghasilkan_blok_yang_sama(): void
    {
        $normal = LaporanPekananCalculator::normalisasiPekan('2026-09-13', self::TA);

        $inverse = LaporanPekananCalculator::normalisasiDariBulanPekan('September', 3, self::TA);

        $this->assertNotNull($inverse);
        $this->assertSame($normal['minggu_ke'], $inverse['minggu_ke']);
        $this->assertSame(
            $normal['mulai']->toDateString(),
            $inverse['mulai']->toDateString()
        );
        $this->assertSame(
            $normal['selesai']->toDateString(),
            $inverse['selesai']->toDateString()
        );
    }

    /**
     * Blok yang ditutup Sabtu 2 Januari 2027 dimulai 27 Desember 2026, jadi
     * tanggal MULAI-nya memang masih di tahun pertama. Yang menentukan tahun
     * kedua adalah tanggal PENUTUP.
     */
    public function test_bulan_januari_sampai_juni_milik_tahun_kedua(): void
    {
        $hasil = LaporanPekananCalculator::normalisasiDariBulanPekan('Januari', 1, self::TA);

        $this->assertNotNull($hasil);
        $this->assertSame(1, $hasil['bulan_ke']);
        $this->assertSame('2027-01-02', $hasil['selesai']->toDateString());
    }

    public function test_bulan_juli_sampai_desember_milik_tahun_pertama(): void
    {
        $hasil = LaporanPekananCalculator::normalisasiDariBulanPekan('Juli', 1, self::TA);

        $this->assertNotNull($hasil);
        $this->assertSame(2026, (int) $hasil['mulai']->format('Y'));
    }

    public function test_nama_bulan_tidak_dikenal_menghasilkan_null(): void
    {
        $this->assertNull(LaporanPekananCalculator::normalisasiDariBulanPekan('Nopember', 1, self::TA));
        $this->assertNull(LaporanPekananCalculator::bulanKeDariNama('Nopember'));
        $this->assertNull(LaporanPekananCalculator::normalisasiDariBulanPekan('Juli', 0, self::TA));
    }

    public function test_daftar_pekan_membatasi_dari_juli_hingga_juni(): void
    {
        $daftar = LaporanPekananCalculator::daftarPekan(self::TA);

        $this->assertGreaterThanOrEqual(40, $daftar->count());
        $this->assertSame(1, $daftar->first()['minggu_ke']);

        // Blok terakhir masih di bulan Juni tahun kedua.
        $terakhir = $daftar->last();
        $this->assertSame(6, $terakhir['bulan_ke']);
        $this->assertSame(2027, (int) $terakhir['periode_selesai']->format('Y'));

        // Tidak ada dua blok dengan kombinasi bulan + peekan yang sama.
        $kunci = $daftar->map(fn ($p) => $p['bulan_ke'].'-'.$p['pekan_dalam_bulan']);
        $this->assertCount($daftar->count(), $kunci->unique());
    }

    #[DataProvider('semester')]
    public function test_semester_dari_bulan(int $bulan, string $harapan): void
    {
        $this->assertSame($harapan, LaporanPekananCalculator::semesterDariBulan($bulan));
    }

    public static function semester(): array
    {
        return [
            [7, '1'], [8, '1'], [9, '1'], [10, '1'], [11, '1'], [12, '1'],
            [1, '2'], [2, '2'], [3, '2'], [4, '2'], [5, '2'], [6, '2'],
        ];
    }

    public function test_periode_otomatis_memakai_sabtu_penutup(): void
    {
        $hasil = LaporanPekananCalculator::periodeOtomatis(Carbon::parse('2026-11-13'));

        $this->assertSame('1', $hasil['semester']);
        $this->assertSame('November', $hasil['bulan']);
        $this->assertSame(2, $hasil['pekan']);
        $this->assertSame(self::TA, $hasil['tahunAjaran']);
    }

    public function test_periode_otomatis_akhir_desember_masuk_semester_genap(): void
    {
        $hasil = LaporanPekananCalculator::periodeOtomatis(Carbon::parse('2026-12-31'));

        $this->assertSame('2', $hasil['semester']);
        $this->assertSame('Januari', $hasil['bulan']);
        $this->assertSame(1, $hasil['pekan']);
    }

    public function test_bulan_untuk_semester(): void
    {
        $this->assertSame(
            ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            LaporanPekananCalculator::bulanUntukSemester('1')
        );
        $this->assertSame(
            ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'],
            LaporanPekananCalculator::bulanUntukSemester('2')
        );
        $this->assertCount(12, LaporanPekananCalculator::bulanUntukSemester(''));
    }

    public function test_pekan_untuk_bulan_hanya_mengambil_bulan_tersebut(): void
    {
        $daftar = LaporanPekananCalculator::pekanUntukBulan('November', self::TA);

        $this->assertCount(4, $daftar);
        $this->assertSame([1, 2, 3, 4], $daftar->pluck('pekan_dalam_bulan')->all());
        $this->assertSame([11], $daftar->pluck('bulan_ke')->unique()->values()->all());
        $this->assertTrue(LaporanPekananCalculator::pekanUntukBulan('Nopember', self::TA)->isEmpty());
        $this->assertTrue(LaporanPekananCalculator::pekanUntukBulan('', self::TA)->isEmpty());
    }

    public function test_urutan_sabtu_setiap_bulan_hanya_satu_nomor(): void
    {
        // Tidak boleh ada "Pekan 6" di bulan mana pun.
        foreach (LaporanPekananCalculator::daftarPekan(self::TA) as $pekan) {
            $this->assertLessThanOrEqual(
                5,
                $pekan['pekan_dalam_bulan'],
                "Pekan {$pekan['minggu_ke']} menghasilkan nomor {$pekan['pekan_dalam_bulan']}"
            );
        }
    }
}
