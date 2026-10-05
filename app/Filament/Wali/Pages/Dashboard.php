<?php

namespace App\Filament\Wali\Pages;

use BackedEnum;

use App\Filament\Wali\Pages\Concerns\MemilikiSantri;
use App\Support\LaporanPekananCalculator;
use App\Support\LaporanPekananData;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Halaman ringkasan untuk wali murid: identitas anak, rekap kehadiran,
 * info wali kelas, dan pintasan ke laporan pekanan.
 *
 * memakai view kustom, bukan widget bawaan Filament, karena isi ringkasan
 * perlu mengikuti tata letak kop surat resmi.
 */
class Dashboard extends BaseDashboard
{
    use MemilikiSantri;

    protected static ?string $title = 'Beranda';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-home';

    protected static ?string $slug = 'beranda';

    protected string $view = 'filament.wali.pages.dashboard';

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return '';
    }

    public ?string $semester = null;

    public ?string $bulan = null;

    public ?string $pekan = null;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getViewData(): array
    {
        $santri = $this->santri();

        $calculator = LaporanPekananData::untuk($santri);
        $periode = $calculator->periodeDariFilter([
            'semester' => $this->semester,
            'bulan' => $this->bulan,
            'pekan' => $this->pekan,
        ]);

        $laporan = $calculator->laporan(
            dari: $periode['dari'],
            sampai: $periode['sampai'],
            semester: $periode['semester'],
            bulan: $periode['bulan'],
            pekan: $periode['pekan'],
        );

        return [
            'santri' => $santri,
            'kelasInfo' => $santri->kelasInfo,
            'periode' => $periode,
            'waliKelas' => $calculator->waliKelas(),
            'jumlahAbsensiTercatat' => $laporan['cntHadir'] + $laporan['cntSakit'] + $laporan['cntIzin'] + $laporan['cntAlfa'],
            'jumlahKbm' => $laporan['kbmLaporans']->count(),
            'jumlahPelanggaran' => $laporan['pelanggaran']->count(),
            'namaBulan' => LaporanPekananCalculator::NAMA_BULAN,
            'daftarPekan' => LaporanPekananCalculator::daftarPekan($periode['tahunAjaran']),
            ...$laporan,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function getWidgets(): array
    {
        return [];
    }
}