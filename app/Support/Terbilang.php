<?php

namespace App\Support;

/**
 * Angka menjadi tulisan bahasa Indonesia, untuk kolom "NILAI HURUF" pada
 * Rapor Bayangan.
 *
 * Di-port VERBATIM dari method `RaporController::terbilang()` di StudentHub
 * supaya angka yang tercetak sama persis, termasuk ketidaktepatan sumbernya:
 * meski judul kolomnya "NILAI HURUF", isinya sebenarnya angka yang ditulis
 * (85 -> "Delapan Puluh Lima"), bukan huruf predikat. Di atas 999 angka
 * dikembalikan apa adanya, sama seperti implementasi aslinya.
 */
class Terbilang
{
    private const KATA = [
        0 => '', 1 => 'Satu', 2 => 'Dua', 3 => 'Tiga', 4 => 'Empat', 5 => 'Lima',
        6 => 'Enam', 7 => 'Tujuh', 8 => 'Delapan', 9 => 'Sembilan', 10 => 'Sepuluh',
        11 => 'Sebelas',
    ];

    /**
     * 0-999 dalam bahasa Indonesia; di atas 999 dikembalikan apa adanya
     * karena sumber aslinya juga begitu.
     */
    public static function dari(int|float|string $number): string
    {
        $number = (int) $number;

        if ($number < 0) {
            return 'Minus '.self::dari(-$number);
        }

        if ($number < 12) {
            return self::KATA[$number];
        }

        if ($number < 20) {
            return self::dari($number - 10).' Belas';
        }

        if ($number < 100) {
            return self::dari((int) ($number / 10)).' Puluh '.self::dari($number % 10);
        }

        if ($number < 200) {
            return 'Seratus '.self::dari($number - 100);
        }

        if ($number < 1000) {
            return self::dari((int) ($number / 100)).' Ratus '.self::dari($number % 100);
        }

        return (string) $number;
    }
}
