<?php

namespace Tests\Unit;

use App\Models\Santri;
use App\Support\LaporanPekananData;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LaporanPekananDataTest extends TestCase
{
    private function data(): LaporanPekananData
    {
        return new LaporanPekananData(new Santri);
    }

    private function sekarang(): Carbon
    {
        return Carbon::parse('2026-11-13 10:00:00');
    }

    public function test_semua_filter_kosong_berarti_satu_tahun_ajaran(): void
    {
        $periode = $this->data()->periodeDariFilter([
            'semester' => '',
            'bulan' => '',
            'pekan' => '',
        ], $this->sekarang());

        $this->assertSame('2026-07-01', $periode['dari']);
        $this->assertSame('2027-06-30', $periode['sampai']);
        $this->assertSame('', $periode['semester']);
        $this->assertNull($periode['bulan']);
        $this->assertNull($periode['pekan']);
    }

    public function test_semua_bulan_mengikuti_semester_terpilih(): void
    {
        $ganjil = $this->data()->periodeDariFilter([
            'semester' => '1',
            'bulan' => '',
            'pekan' => '',
        ], $this->sekarang());

        $this->assertSame('2026-07-01', $ganjil['dari']);
        $this->assertSame('2026-12-31', $ganjil['sampai']);

        $genap = $this->data()->periodeDariFilter([
            'semester' => '2',
            'bulan' => '',
            'pekan' => '',
        ], $this->sekarang());

        $this->assertSame('2027-01-01', $genap['dari']);
        $this->assertSame('2027-06-30', $genap['sampai']);
    }

    public function test_bulan_tanpa_pekan_berarti_satu_bulan_penuh(): void
    {
        $periode = $this->data()->periodeDariFilter([
            'semester' => '1',
            'bulan' => 'November',
            'pekan' => '',
        ], $this->sekarang());

        $this->assertSame('2026-11-01', $periode['dari']);
        $this->assertSame('2026-11-30', $periode['sampai']);
        $this->assertSame('November', $periode['bulan']);
        $this->assertNull($periode['pekan']);
    }

    public function test_bulan_dan_pekan_berarti_satu_blok_minggu_sabtu(): void
    {
        $periode = $this->data()->periodeDariFilter([
            'semester' => '1',
            'bulan' => 'November',
            'pekan' => '2',
        ], $this->sekarang());

        $this->assertSame('2026-11-08', $periode['dari']);
        $this->assertSame('2026-11-14', $periode['sampai']);
        $this->assertSame('2', $periode['pekan']);
    }

    public function test_tanpa_filter_memakai_bulan_berjalan(): void
    {
        $periode = $this->data()->periodeDariFilter([], $this->sekarang());

        $this->assertSame('2026-11-01', $periode['dari']);
        $this->assertSame('2026-11-30', $periode['sampai']);
        $this->assertSame('1', $periode['semester']);
        $this->assertSame('November', $periode['bulan']);
    }
}
