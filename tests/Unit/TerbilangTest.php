<?php

namespace Tests\Unit;

use App\Support\Terbilang;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Ekspektasi dihitung ulang dari StudentHub RaporController::terbilang(),
 * bukan disalin dari output kelas ini, supaya test tetap punya kekuatan
 * sebagai penangkap regresi.
 *
 * Perhatikan spasi ganda di akhir beberapa hasil ("Dua Puluh "). Itu memang
 * hasil algoritma StudentHub - `terbilang(0)` mengembalikan string kosong lalu
 * langsung disambung dengan spasi - dan sengaja dikunci agar kolaborator tidak
 * "memperbaiki" format rapor tanpa sengaja.
 */
class TerbilangTest extends TestCase
{
    public static function angkaDanhasil(): array
    {
        return [
            [0, ''],
            [1, 'Satu'],
            [5, 'Lima'],
            [10, 'Sepuluh'],
            [11, 'Sebelas'],
            [12, 'Dua Belas'],
            [15, 'Lima Belas'],
            [19, 'Sembilan Belas'],
            [20, 'Dua Puluh '],
            [21, 'Dua Puluh Satu'],
            [25, 'Dua Puluh Lima'],
            [30, 'Tiga Puluh '],
            [85, 'Delapan Puluh Lima'],
            [90, 'Sembilan Puluh '],
            [99, 'Sembilan Puluh Sembilan'],
            [100, 'Seratus '],
            [101, 'Seratus Satu'],
            [110, 'Seratus Sepuluh'],
            [125, 'Seratus Dua Puluh Lima'],
            [200, 'Dua Ratus '],
            [999, 'Sembilan Ratus Sembilan Puluh Sembilan'],
        ];
    }

    #[DataProvider('angkaDanhasil')]
    public function test_nilai_angka_diterjemahkan_seperti_referensi(int $angka, string $hasil): void
    {
        $this->assertSame($hasil, Terbilang::dari($angka));
    }

    public function test_nilai_di_atas_seribu_dikembalikan_apa_adanya(): void
    {
        $this->assertSame('1000', Terbilang::dari(1000));
        $this->assertSame('1500', Terbilang::dari(1500));
    }

    public function test_bilangan_desimal_dipotong_bukan_dibulatkan(): void
    {
        // Nilai STS disimpan sebagai float, jadi pecahan wajib terpotong
        // dengan (int), sama seperti casting di StudentHub.
        $this->assertSame('Delapan Puluh Lima', Terbilang::dari(85.9));
        $this->assertSame('Seratus ', Terbilang::dari(100.4));
    }

    public function test_nilai_berbentuk_teks_diterima(): void
    {
        $this->assertSame('Delapan Puluh Lima', Terbilang::dari('85'));
    }

    /**
     * StudentHub tidak menangani bilangan negatif sama sekali: `terbilang(-5)`
     * masuk cabang `< 12` lalu membaca indeks array negatif dan menghasilkan
     * warning. Versi di sini menutupnya.
     */
    public function test_bilangan_negatif_ditulis_dengan_kata_minus(): void
    {
        $this->assertSame('Minus Lima', Terbilang::dari(-5));
        $this->assertSame('Minus Dua Puluh Lima', Terbilang::dari(-25));
    }
}
