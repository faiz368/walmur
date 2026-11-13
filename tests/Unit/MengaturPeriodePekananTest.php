<?php

namespace Tests\Unit;

use App\Filament\Wali\Pages\Concerns\MengaturPeriodePekanan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MengaturPeriodePekananTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-13 10:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function filter(): object
    {
        return new class
        {
            use MengaturPeriodePekanan;
        };
    }

    public function test_pilih_periode_sekarang_mengisi_semester_bulan_dan_pekan(): void
    {
        $filter = $this->filter();
        $filter->pilihPeriodeSekarang();

        $this->assertSame('1', $filter->semester);
        $this->assertSame('November', $filter->bulan);
        $this->assertSame('2', $filter->pekan);
        $this->assertSame(
            ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            $filter->pilihanBulan()
        );
        $this->assertCount(4, $filter->daftarPekanFilter());
    }

    public function test_query_string_dipakai_untuk_pilihan_manual(): void
    {
        $this->app->instance('request', Request::create('/?semester=2&bulan=Januari&pekan=1'));

        $filter = $this->filter();
        $filter->mountMengaturPeriodePekanan();

        $this->assertSame('2', $filter->semester);
        $this->assertSame('Januari', $filter->bulan);
        $this->assertSame('1', $filter->pekan);
    }

    public function test_query_string_kosong_berarti_semua(): void
    {
        $this->app->instance('request', Request::create('/?semester=&bulan=&pekan='));

        $filter = $this->filter();
        $filter->mountMengaturPeriodePekanan();

        $this->assertSame('', $filter->semester);
        $this->assertSame('', $filter->bulan);
        $this->assertSame('', $filter->pekan);
    }

    public function test_ganti_bulan_menyesuaikan_semester_dan_pekan(): void
    {
        $filter = $this->filter();
        $filter->pilihPeriodeSekarang();

        $filter->updatedBulan('Januari');

        $this->assertSame('2', $filter->semester);
        $this->assertSame('Januari', $filter->bulan);
        $this->assertSame('', $filter->pekan);

        $filter->updatedBulan('November');

        $this->assertSame('1', $filter->semester);
        $this->assertSame('2', $filter->pekan);
    }

    public function test_ganti_semester_menyesuaikan_bulan_dan_pekan(): void
    {
        $filter = $this->filter();
        $filter->pilihPeriodeSekarang();

        $filter->updatedSemester('2');

        $this->assertSame('2', $filter->semester);
        $this->assertSame('Januari', $filter->bulan);
        $this->assertSame('', $filter->pekan);

        $filter->updatedSemester('1');

        $this->assertSame('1', $filter->semester);
        $this->assertSame('November', $filter->bulan);
        $this->assertSame('2', $filter->pekan);
    }

    public function test_semua_semester_mempertahankan_bulan_dan_pekan(): void
    {
        $filter = $this->filter();
        $filter->pilihPeriodeSekarang();

        $filter->updatedSemester('');

        $this->assertSame('', $filter->semester);
        $this->assertSame('November', $filter->bulan);
        $this->assertSame('2', $filter->pekan);
    }

    public function test_semua_bulan_menghapus_pekan(): void
    {
        $filter = $this->filter();
        $filter->pilihPeriodeSekarang();

        $filter->updatedBulan('');

        $this->assertSame('', $filter->bulan);
        $this->assertSame('', $filter->pekan);
        $this->assertTrue($filter->daftarPekanFilter()->isEmpty());
    }

    public function test_pekan_di_luar_bulan_terpilih_direset(): void
    {
        $filter = $this->filter();
        $filter->pilihPeriodeSekarang();

        $filter->updatedPekan('5');
        $this->assertSame('', $filter->pekan);

        $filter->updatedPekan('3');
        $this->assertSame('3', $filter->pekan);
    }
}
