<?php

namespace App\Filament\Wali\Pages\Concerns;

use App\Support\LaporanPekananCalculator;
use Illuminate\Support\Collection;

/**
 * Filter semester/bulan/pekan yang terisi otomatis dari tanggal berjalan,
 * tetapi tetap bisa diubah manual.
 */
trait MengaturPeriodePekanan
{
    public ?string $semester = null;

    public ?string $bulan = null;

    public ?string $pekan = null;

    public function mountMengaturPeriodePekanan(): void
    {
        $otomatis = LaporanPekananCalculator::periodeOtomatis();
        $query = request()->query();

        $semester = $this->nilaiQuery($query, 'semester');
        $bulan = $this->nilaiQuery($query, 'bulan');
        $pekan = $this->nilaiQuery($query, 'pekan');

        $this->semester = $this->semesterValid($semester) ? $semester : $otomatis['semester'];
        $this->bulan = $this->bulanValid($bulan) ? $bulan : $otomatis['bulan'];
        $this->pekan = $this->pekanValid($pekan) ? $pekan : (string) $otomatis['pekan'];

        $this->rapikanKombinasiPeriode();
    }

    public function updatedSemester(?string $value): void
    {
        $value = trim((string) $value);
        $this->semester = $value;

        if ($value === '') {
            $this->rapikanKombinasiPeriode();

            return;
        }

        if (! in_array($value, ['1', '2'], true)) {
            $this->semester = '';
            $this->rapikanKombinasiPeriode();

            return;
        }

        $otomatis = LaporanPekananCalculator::periodeOtomatis();
        $bulanTersedia = LaporanPekananCalculator::bulanUntukSemester($value);
        $bulanBerubah = ! in_array($this->bulan, $bulanTersedia, true);

        if ($bulanBerubah) {
            $this->bulan = in_array($otomatis['bulan'], $bulanTersedia, true)
                ? $otomatis['bulan']
                : $bulanTersedia[0];
        }

        if ($this->bulan === $otomatis['bulan'] && $value === $otomatis['semester']) {
            $this->pekan = (string) $otomatis['pekan'];
        } elseif ($bulanBerubah) {
            $this->pekan = '';
        } else {
            $this->pekan = $this->pekanUntukBulanTerpilih($this->pekan);
        }
    }

    public function updatedBulan(?string $value): void
    {
        $value = trim((string) $value);
        $this->bulan = $value;

        if ($value === '') {
            $this->pekan = '';

            return;
        }

        $bulanKe = LaporanPekananCalculator::bulanKeDariNama($value);

        if ($bulanKe === null) {
            $this->bulan = '';
            $this->pekan = '';

            return;
        }

        $this->semester = LaporanPekananCalculator::semesterDariBulan($bulanKe);

        $otomatis = LaporanPekananCalculator::periodeOtomatis();
        $this->pekan = $value === $otomatis['bulan'] && $this->semester === $otomatis['semester']
            ? (string) $otomatis['pekan']
            : '';
    }

    public function updatedPekan(?string $value): void
    {
        $value = trim((string) $value);
        $this->pekan = $value;

        if ($value === '') {
            return;
        }

        $this->pekan = $this->pekanUntukBulanTerpilih($value);
    }

    public function pilihPeriodeSekarang(): void
    {
        $otomatis = LaporanPekananCalculator::periodeOtomatis();

        $this->semester = $otomatis['semester'];
        $this->bulan = $otomatis['bulan'];
        $this->pekan = (string) $otomatis['pekan'];
    }

    /**
     * @return list<string>
     */
    public function pilihanBulan(): array
    {
        return LaporanPekananCalculator::bulanUntukSemester((string) $this->semester);
    }

    /**
     * @return Collection<int, array>
     */
    public function daftarPekanFilter(): Collection
    {
        if (blank($this->bulan)) {
            return collect();
        }

        return LaporanPekananCalculator::pekanUntukBulan($this->bulan);
    }

    private function rapikanKombinasiPeriode(): void
    {
        if (filled($this->bulan) && filled($this->semester)) {
            $bulanKe = LaporanPekananCalculator::bulanKeDariNama($this->bulan);

            if ($bulanKe !== null) {
                $this->semester = LaporanPekananCalculator::semesterDariBulan($bulanKe);
            }
        }

        if (blank($this->bulan)) {
            $this->pekan = '';
        } elseif (filled($this->pekan)) {
            $this->pekan = $this->pekanUntukBulanTerpilih($this->pekan);
        }
    }

    private function pekanUntukBulanTerpilih(?string $pekan): string
    {
        if (blank($this->bulan) || blank($pekan)) {
            return '';
        }

        $valid = LaporanPekananCalculator::pekanUntukBulan($this->bulan)
            ->contains(fn (array $item): bool => (string) $item['pekan_dalam_bulan'] === (string) $pekan);

        return $valid ? (string) $pekan : '';
    }

    private function nilaiQuery(array $query, string $key): ?string
    {
        if (! array_key_exists($key, $query)) {
            return null;
        }

        return trim((string) $query[$key]);
    }

    private function semesterValid(?string $semester): bool
    {
        return $semester !== null && in_array($semester, ['', '1', '2'], true);
    }

    private function bulanValid(?string $bulan): bool
    {
        return $bulan !== null
            && ($bulan === '' || LaporanPekananCalculator::bulanKeDariNama($bulan) !== null);
    }

    private function pekanValid(?string $pekan): bool
    {
        return $pekan !== null
            && ($pekan === '' || (ctype_digit($pekan) && (int) $pekan >= 1 && (int) $pekan <= 5));
    }
}
