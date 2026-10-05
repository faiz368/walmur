<?php

namespace App\Filament\Wali\Pages;

use App\Filament\Wali\Pages\Concerns\MemilikiSantri;
use App\Support\LaporanPekananData;
use App\Support\RaporBayanganData;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

/**
 * Halaman Rapor Bayangan (STS) untuk wali murid.
 */
class RaporBayangan extends Page
{
    use MemilikiSantri;

    protected static ?string $title = 'Rapor Bayangan';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $slug = 'rapor-bayangan';

    protected string $view = 'filament.wali.pages.rapor-bayangan';

    public string $semesterRapor = '1';

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getViewData(): array
    {
        $santri = $this->santri();
        $calculator = LaporanPekananData::untuk($santri);

        return [
            'santri' => $santri,
            'waliKelas' => $calculator->waliKelas(),
            'kepalaSekolah' => $calculator->kepalaSekolah(),
            'rapor' => RaporBayanganData::untuk($santri)->untukSemester($this->semesterRapor),
            'tanggalCetakRapor' => 'Bogor, '.Carbon::now()->isoFormat('D MMMM Y'),
        ];
    }
}
