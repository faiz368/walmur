<?php

namespace App\Support;

use App\Enums\StatusAbsensi;
use App\Models\AbsensiSantri;
use App\Models\Kelas;
use App\Models\LaporanPekanan;
use App\Models\PelanggaranSantri;
use App\Models\Santri;
use App\Models\SantriIzin;
use App\Models\SantriSakit;
use App\Models\TesHafalan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Menyusun data halaman LaporanPekanan dari database baca-saja.
 *
 * Query disalin dari StudentHub/app/Http/Controllers/LaporanOrtuController.php
 * (method show) supaya angka pada portal ini sama persis dengan halaman
 * referensi.
 */
class LaporanPekananData
{
    public function __construct(private readonly Santri $santri) {}

    public static function untuk(Santri $santri): self
    {
        return new self($santri);
    }

    /**
     * Keterangan periode aktif dari query string.
     *
     * @param  array{semester?: string|null, bulan?: string|null, pekan?: string|null}  $filter
     * @return array{dari: string, sampai: string, semester: string, bulan: ?string, pekan: ?string, tahunAjaran: string}
     */
    public function periodeDariFilter(array $filter, ?Carbon $now = null): array
    {
        $now ??= Carbon::now();
        $tahunAjaran = LaporanPekananCalculator::tahunAjaranAktif($now);

        $semester = (string) ($filter['semester'] ?? LaporanPekananCalculator::semesterAktif($now));
        $bulan = filled($filter['bulan'] ?? null) ? trim((string) $filter['bulan']) : null;
        $pekan = filled($filter['pekan'] ?? null) ? trim((string) $filter['pekan']) : null;

        // Bulan + pekan -> blok Minggu-Sabtu yang ditutup Sabtu penutup bulan itu.
        if ($bulan !== null && $pekan !== null) {
            $snap = LaporanPekananCalculator::normalisasiDariBulanPekan($bulan, (int) $pekan, $tahunAjaran);

            if ($snap !== null) {
                return [
                    'dari' => $snap['mulai']->toDateString(),
                    'sampai' => $snap['selesai']->toDateString(),
                    'semester' => $semester,
                    'bulan' => $bulan,
                    'pekan' => $pekan,
                    'tahunAjaran' => $tahunAjaran,
                ];
            }
        }

        // Hanya bulan (atau tidak ada filter) -> satu bulan penuh.
        $bulanKe = $bulan !== null
            ? LaporanPekananCalculator::bulanKeDariNama($bulan)
            : (int) $now->format('n');

        if ($bulanKe === null) {
            $bulanKe = (int) $now->format('n');
        }

        // Juli-Desember milik tahun pertama tahun ajaran, Januari-Juni tahun kedua.
        $tahunAwal = (int) $now->format('n') >= 7 ? (int) $now->format('Y') : ((int) $now->format('Y') - 1);
        $tahun = $bulanKe >= 7 ? $tahunAwal : $tahunAwal + 1;

        return [
            'dari' => Carbon::create($tahun, $bulanKe, 1)->startOfMonth()->toDateString(),
            'sampai' => Carbon::create($tahun, $bulanKe, 1)->endOfMonth()->toDateString(),
            'semester' => $semester,
            'bulan' => $bulan,
            'pekan' => $pekan,
            'tahunAjaran' => $tahunAjaran,
        ];
    }

    /**
     * Kumpulan data laporan untuk satu periode.
     *
     * @return array{kbmLaporans: Collection, absensis: Collection, sakits: Collection, izins: Collection, pelanggaran: Collection, tesHafalans: Collection, cntHadir: int, cntSakit: int, cntIzin: int, cntAlfa: int}
     */
    public function laporan(string $dari, string $sampai, string $semester, ?string $bulan = null, ?string $pekan = null): array
    {
        $santri = $this->santri;

        $kbmLaporans = $this->laporanKbm($dari, $sampai, $semester, $bulan, $pekan);

        $absensis = AbsensiSantri::where('santri_id', $santri->id)
            ->whereBetween('tanggal', [$dari, $sampai])
            ->orderBy('tanggal')
            ->get();

        $sakits = SantriSakit::where('santri_id', $santri->id)
            ->whereBetween('tanggal_sakit', [$dari, $sampai])
            ->orderByDesc('tanggal_sakit')
            ->get();

        $izins = SantriIzin::where('santri_id', $santri->id)
            ->whereDate('tanggal_mulai', '<=', $sampai)
            ->whereDate('tanggal_akhir', '>=', $dari)
            ->orderByDesc('tanggal_mulai')
            ->get();

        $pelanggaran = PelanggaranSantri::with('kategori')
            ->where('santri_id', $santri->id)
            ->whereBetween('tanggal_pelanggaran', [$dari, $sampai])
            ->orderByDesc('tanggal_pelanggaran')
            ->get();

        $tesHafalans = TesHafalan::where('santri_id', $santri->id)->get();

        return [
            'kbmLaporans' => $kbmLaporans,
            'absensis' => $absensis,
            'sakits' => $sakits,
            'izins' => $izins,
            'pelanggaran' => $pelanggaran,
            'tesHafalans' => $tesHafalans,
            ...$this->rekapAbsensi($absensis),
        ];
    }

    /**
     * Bentuk per-mapel saja: `laporan_pekanans` di StudentHub juga memuat 2
     * baris legacy (santri_id + kolom tahfizh, kelas '-', periode NULL) yang
     * sengaja tidak dipakai portal ini.
     *
     * @return Collection<int, LaporanPekanan>
     */
    public function laporanKbm(string $dari, string $sampai, string $semester, ?string $bulan, ?string $pekan): Collection
    {
        if (blank($this->santri->kelas)) {
            return collect();
        }

        $query = LaporanPekanan::query()
            ->with([
                'setting.guru',
                'guru',
                'nilaiSantris' => fn ($q) => $q->where('santri_id', $this->santri->id),
            ])
            ->where('kelas', $this->santri->kelas);

        if (filled($semester)) {
            $query->where('semester', $semester);
        }

        if (filled($bulan)) {
            $query->where(function ($q) use ($bulan, $dari, $sampai) {
                $q->where('bulan', $bulan)
                    ->orWhere('bulan_ke', LaporanPekananCalculator::bulanKeDariNama($bulan))
                    ->orWhere(function ($q2) use ($dari, $sampai) {
                        $q2->whereBetween('periode_mulai', [$dari, $sampai])
                            ->orWhereBetween('periode_selesai', [$dari, $sampai]);
                    });
            });
        } else {
            $query->where(function ($q) use ($dari, $sampai) {
                $q->whereBetween('periode_mulai', [$dari, $sampai])
                    ->orWhereBetween('periode_selesai', [$dari, $sampai])
                    ->orWhere(function ($q2) use ($dari, $sampai) {
                        $q2->where('periode_mulai', '<=', $dari)
                            ->where('periode_selesai', '>=', $sampai);
                    });
            });
        }

        if (filled($pekan)) {
            $query->where(function ($q) use ($pekan) {
                $q->where('pekan', $pekan)
                    ->orWhere('pekan_dalam_bulan', (int) $pekan);
            });
        }

        return $query->orderBy('mata_pelajaran')->get();
    }

    /**
     * Rekap kehadiran dengan normalisasi Alfa/Alpha.
     *
     * @param  Collection<int, AbsensiSantri>  $absensis
     * @return array{cntHadir: int, cntSakit: int, cntIzin: int, cntAlfa: int}
     */
    public function rekapAbsensi(Collection $absensis): array
    {
        $jumlah = [
            StatusAbsensi::Hadir->value => 0,
            StatusAbsensi::Sakit->value => 0,
            StatusAbsensi::Izin->value => 0,
            StatusAbsensi::Alfa->value => 0,
        ];

        foreach ($absensis as $absensi) {
            $status = StatusAbsensi::normalize($absensi->status);

            if ($status !== null) {
                $jumlah[$status->value]++;
            }
        }

        return [
            'cntHadir' => $jumlah[StatusAbsensi::Hadir->value],
            'cntSakit' => $jumlah[StatusAbsensi::Sakit->value],
            'cntIzin' => $jumlah[StatusAbsensi::Izin->value],
            'cntAlfa' => $jumlah[StatusAbsensi::Alfa->value],
        ];
    }

    /**
     * Wali kelas dari tabel `kelas`. Kolom `wali_kelas_nama` dipakai duluan
     * karena sudah berupa teks dan tidak bergantung pada relasi `users`.
     *
     * @return array{nama: ?string, nip: ?string}
     */
    public function waliKelas(): array
    {
        $kelas = null;

        if (filled($this->santri->kelas_id)) {
            $kelas = Kelas::where('id', $this->santri->kelas_id)->first();
        } elseif (filled($this->santri->kelas)) {
            $kelas = Kelas::where('nama_kelas', $this->santri->kelas)->first();
        }

        if ($kelas === null) {
            return ['nama' => null, 'nip' => null];
        }

        return [
            'nama' => $kelas->wali_kelas_nama ?: $kelas->waliKelas?->name,
            'nip' => $kelas->wali_kelas_nip,
        ];
    }

    /**
     * Kepala Sekolah berdasarkan jenjang kelas santri:
     * - SMP: Kelas VII, VIII, IX (atau 7, 8, 9)
     * - SMA: Kelas X, XI, XII (atau 10, 11, 12)
     *
     * @return array{jabatan: string, jenjang: string, nama: ?string, nip: ?string}
     */
    public function kepalaSekolah(): array
    {
        $kelasNama = strtoupper(trim((string) ($this->santri->kelas ?? '')));

        if (blank($kelasNama) && filled($this->santri->kelas_id)) {
            $kelas = Kelas::find($this->santri->kelas_id);
            $kelasNama = strtoupper(trim((string) ($kelas?->nama_kelas ?? $kelas?->tingkat ?? '')));
        }

        $isSma = preg_match('/\b(X|XI|XII|10|11|12)\b/i', $kelasNama)
            || str_contains($kelasNama, 'SMA')
            || str_contains($kelasNama, 'MA');

        if ($isSma) {
            $jenjang = 'SMA';
            $jabatan = 'Kepala Sekolah SMA';
        } else {
            $jenjang = 'SMP';
            $jabatan = 'Kepala Sekolah SMP';
        }

        return [
            'jenjang' => $jenjang,
            'jabatan' => $jabatan,
            'nama' => null,
            'nip' => null,
        ];
    }
}