<?php

namespace App\Filament\Wali\Pages;

use App\Filament\Wali\Pages\Concerns\MemilikiSantri;
use App\Filament\Wali\Pages\Concerns\MengaturPeriodePekanan;
use App\Support\LaporanPekananData;
use App\Support\RaporBayanganData;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

/**
 * Laporan pekanan wali murid: KBM per mata pelajaran, kesiswaan & kesantrian,
 * capaian tahfizh, dan rapor bayangan (STS). Query-nya mengikuti halaman
 * StudentHub /laporan-ortu/wali/laporan/{santri} dan
 * /rapor/cetak-bayangan/{santri}.
 *
 * Anak yang ditampilkan selalu milik wali yang sedang login; halaman
 * ini tidak menerima parameter delegasi dari URL.
 */
class LaporanPekanan extends Page
{
    use MemilikiSantri;
    use MengaturPeriodePekanan;

    protected static ?string $title = 'Laporan Pekanan Anak';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $slug = 'laporan-pekanan';

    protected string $view = 'filament.wali.pages.laporan-pekanan';

    public string $tabAktif = 'kbm';

    /**
     * Semester untuk tab Rapor Bayangan, terpisah dari $semester di atas.
     *
     * Dipisah karena rapor hanya mengenal semester 1/2. Filter "Semua
     * Semester" milik Laporan Pekanan tidak punya padanannya di sana, dan
     * menyimpan keduanya pada satu properti akan membuat dua layar saling
     * ketergantungan.
     */
    public string $semesterRapor = '1';

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
            'periode' => $periode,
            'waliKelas' => $calculator->waliKelas(),
            'pilihanBulan' => $this->pilihanBulan(),
            'daftarPekan' => $this->daftarPekanFilter(),
            'nomorDokumen' => 'LAP/'.Carbon::now()->format('Ym').'/'.$santri->id,
            'tanggalCetak' => Carbon::now()->translatedFormat('d F Y'),
            'tanggalMulai' => Carbon::parse($periode['dari'])->translatedFormat('d F Y'),
            'tanggalSelesai' => Carbon::parse($periode['sampai'])->translatedFormat('d F Y'),
            'rapor' => RaporBayanganData::untuk($santri)->untukSemester($this->semesterRapor),
            'tanggalCetakRapor' => 'Bogor, '.Carbon::now()->isoFormat('D MMMM Y'),
            ...$laporan,
        ];
    }
}
