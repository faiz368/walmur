<?php

namespace Tests\Unit;

use App\Enums\StatusAbsensi;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StatusAbsensiTest extends TestCase
{
    /**
     * Data di `absensi_santris` StudentHub memakai ejaan 'Alpha', sedangkan
     * dokumen resmi memakai 'Alfa'. Keduanya harus collapse ke enum yang sama.
     */
    #[DataProvider('statusMentah')]
    public function test_normalisasi_menyeragamkan_ejaan(?string $mentah, ?StatusAbsensi $harapan): void
    {
        $this->assertSame($harapan, StatusAbsensi::normalize($mentah));
    }

    public static function statusMentah(): array
    {
        return [
            ['Hadir', StatusAbsensi::Hadir],
            ['hadir', StatusAbsensi::Hadir],
            ['HADIR', StatusAbsensi::Hadir],
            [' Hadir ', StatusAbsensi::Hadir],
            ['Sakit', StatusAbsensi::Sakit],
            ['Izin', StatusAbsensi::Izin],
            ['Alfa', StatusAbsensi::Alfa],
            ['Alpha', StatusAbsensi::Alfa],
            ['ALFA', StatusAbsensi::Alfa],
            ['alfa', StatusAbsensi::Alfa],
            ['Bolkir', null],
            ['', null],
            [null, null],
        ];
    }

    public function test_hanya_nilai_dikenal_yang_diterima(): void
    {
        $this->assertNull(StatusAbsensi::normalize('Tidak Diketahui'));
        $this->assertNull(StatusAbsensi::normalize('0'));
    }
}