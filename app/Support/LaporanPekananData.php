<?php

namespace App\Support;

use App\Enums\StatusAbsensi;
use App\Models\AbsensiHalaqoh;
use App\Models\AbsensiSantri;
use App\Models\Kelas;
use App\Models\LaporanPekanan;
use App\Models\LaporanPekananKelas;
use App\Models\PelanggaranSantri;
use App\Models\Santri;
use App\Models\SantriIzin;
use App\Models\SantriSakit;
use App\Models\Setoran;
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

        $semester = $filter['semester'] ?? null;
        $semester = $semester === null
            ? LaporanPekananCalculator::semesterAktif($now)
            : trim((string) $semester);

        if (! in_array($semester, ['', '1', '2'], true)) {
            $semester = LaporanPekananCalculator::semesterAktif($now);
        }

        $bulan = $filter['bulan'] ?? null;
        $bulan = $bulan === null ? null : trim((string) $bulan);

        $pekan = $filter['pekan'] ?? null;
        $pekan = $pekan === null ? null : trim((string) $pekan);
        $pekan = ctype_digit((string) $pekan) && (int) $pekan >= 1 && (int) $pekan <= 5
            ? (string) (int) $pekan
            : null;

        // Tanpa filter bulan -> perilaku lama: bulan berjalan.
        $bulan ??= LaporanPekananCalculator::NAMA_BULAN[(int) $now->format('n') - 1];

        $bulanKe = $bulan === '' ? null : LaporanPekananCalculator::bulanKeDariNama($bulan);

        if ($bulan !== '' && $bulanKe === null) {
            $bulan = LaporanPekananCalculator::NAMA_BULAN[(int) $now->format('n') - 1];
            $bulanKe = (int) $now->format('n');
            $pekan = null;
        }

        // Bulan + pekan -> blok Minggu-Sabtu yang ditutup Sabtu penutup bulan itu.
        if ($bulanKe !== null && $pekan !== null) {
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

        // Juli-Desember milik tahun pertama tahun ajaran, Januari-Juni tahun kedua.
        $tahunAwal = (int) $now->format('n') >= 7 ? (int) $now->format('Y') : ((int) $now->format('Y') - 1);

        // Hanya bulan (atau pekan "Semua") -> satu bulan penuh.
        if ($bulanKe !== null) {
            $tahun = $bulanKe >= 7 ? $tahunAwal : $tahunAwal + 1;

            return [
                'dari' => Carbon::create($tahun, $bulanKe, 1)->startOfMonth()->toDateString(),
                'sampai' => Carbon::create($tahun, $bulanKe, 1)->endOfMonth()->toDateString(),
                'semester' => $semester,
                'bulan' => $bulan,
                'pekan' => null,
                'tahunAjaran' => $tahunAjaran,
            ];
        }

        // Bulan "Semua" -> rentang penuh semester terpilih, atau satu tahun ajaran.
        [$dari, $sampai] = match ($semester) {
            '1' => [Carbon::create($tahunAwal, 7, 1), Carbon::create($tahunAwal, 12, 31)],
            '2' => [Carbon::create($tahunAwal + 1, 1, 1), Carbon::create($tahunAwal + 1, 6, 30)],
            default => [Carbon::create($tahunAwal, 7, 1), Carbon::create($tahunAwal + 1, 6, 30)],
        };

        return [
            'dari' => $dari->toDateString(),
            'sampai' => $sampai->toDateString(),
            'semester' => $semester,
            'bulan' => null,
            'pekan' => null,
            'tahunAjaran' => $tahunAjaran,
        ];
    }

    /**
     * Kumpulan data laporan untuk satu periode.
     *
     * @return array{kbmLaporans: Collection, tahfizhLaporans: Collection, tahfizhRingkasan: array, absensis: Collection, sakits: Collection, izins: Collection, pelanggaran: Collection, tesHafalans: Collection, cntHadir: int, cntSakit: int, cntIzin: int, cntAlfa: int}
     */
    public function laporan(string $dari, string $sampai, string $semester, ?string $bulan = null, ?string $pekan = null): array
    {
        $santri = $this->santri;

        $kbmLaporans = $this->laporanKbm($dari, $sampai, $semester, $bulan, $pekan);
        $tahfizhLaporans = $this->laporanTahfizh($dari, $sampai, $semester, $bulan, $pekan);
        $tahfizhRingkasan = $this->ringkasanTahfizh($dari, $sampai, $tahfizhLaporans);

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
            'tahfizhLaporans' => $tahfizhLaporans,
            'tahfizhRingkasan' => $tahfizhRingkasan,
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
     * Laporan tahfizh yang sudah dipublikasikan untuk santri pada satu periode.
     *
     * Hanya detail milik santri yang sedang login yang di-eager-load, supaya
     * capaian/catatan santri lain tidak pernah terbaca oleh portal wali.
     *
     * @return Collection<int, LaporanPekananKelas>
     */
    public function laporanTahfizh(string $dari, string $sampai, string $semester, ?string $bulan, ?string $pekan): Collection
    {
        $milikSantri = function ($query) {
            $query->where(function ($q) {
                $q->where('santri_id', $this->santri->id);

                if (filled($this->santri->nis)) {
                    $q->orWhere(function ($q2) {
                        $q2->whereNull('santri_id')
                            ->where('nis', $this->santri->nis);
                    });
                }
            });
        };

        return LaporanPekananKelas::query()
            ->where('status', 'published')
            ->where('periode_mulai', '<=', $sampai)
            ->where('periode_selesai', '>=', $dari)
            ->with(['details' => function ($query) use ($milikSantri) {
                $milikSantri($query);
                $query->orderBy('urutan');
            }])
            ->whereHas('details', $milikSantri)
            ->orderBy('periode_mulai')
            ->orderBy('id')
            ->get();
    }

    /**
     * Ringkasan tab Tahfizh dari laporan pekanan + data setoran/presensi.
     *
     * @param  Collection<int, LaporanPekananKelas>  $laporanTahfizh
     * @return array{
     *     guru: array{nama: ?string, halaqah: ?string},
     *     capaian: array{target: ?float, capaian: ?float, persentase: ?float, catatan: ?string, ziyadah_terakhir: ?Setoran, jumlah_ziyadah: int},
     *     murojaah: array{jumlah: int, halaman: float, items: Collection<int, Setoran>},
     *     presensi: array{rekap: array<string, int>, items: Collection<int, AbsensiHalaqoh>},
     *     hafalan: array{juz: float, halaman: float, target_juz: float, total_surah: int}
     * }
     */
    public function ringkasanTahfizh(string $dari, string $sampai, Collection $laporanTahfizh): array
    {
        $detailTerakhir = $laporanTahfizh->last()?->details->first();

        $ziyadah = Setoran::query()
            ->where('santri_id', $this->santri->id)
            ->ziyadah()
            ->whereBetween('tanggal', [$dari, $sampai])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();

        $murojaah = Setoran::query()
            ->where('santri_id', $this->santri->id)
            ->murojaah()
            ->whereBetween('tanggal', [$dari, $sampai])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();

        $absensiHalaqoh = AbsensiHalaqoh::query()
            ->where('santri_id', $this->santri->id)
            ->whereBetween('tanggal', [$dari, $sampai])
            ->orderBy('tanggal')
            ->get();

        return [
            'guru' => $this->keteranganHalaqoh(),
            'capaian' => [
                'target' => $detailTerakhir?->target_pekanan,
                'capaian' => $detailTerakhir?->capaian_pekanan,
                'persentase' => $detailTerakhir?->persentase,
                'catatan' => $detailTerakhir?->catatan,
                'ziyadah_terakhir' => $ziyadah->first(),
                'jumlah_ziyadah' => $ziyadah->count(),
            ],
            'murojaah' => [
                'jumlah' => $murojaah->count(),
                'halaman' => (float) $murojaah->sum(fn (Setoran $setoran) => (int) $setoran->jumlah_halaman_murajaah),
                'items' => $murojaah,
            ],
            'presensi' => [
                'rekap' => $this->rekapPresensiHalaqoh($laporanTahfizh, $absensiHalaqoh),
                'items' => $absensiHalaqoh,
            ],
            'hafalan' => [
                'juz' => (float) ($detailTerakhir?->total_hafalan_juz ?? $this->santri->current_juz ?? 0),
                'halaman' => (float) ($detailTerakhir?->total_hafalan_halaman ?? 0),
                'target_juz' => (float) ($this->santri->target_juz ?? 0),
                'total_surah' => (int) ($this->santri->total_surah_hafal ?? 0),
            ],
        ];
    }

    /**
     * @return array{nama: ?string, halaqah: ?string}
     */
    public function keteranganHalaqoh(): array
    {
        $halaqah = $this->santri->halaqah;

        return [
            'nama' => $halaqah?->ustadz?->name,
            'halaqah' => $halaqah?->nama_halaqah ?: $this->santri->halaqoh,
        ];
    }

    /**
     * @param  Collection<int, LaporanPekananKelas>  $laporanTahfizh
     * @param  Collection<int, AbsensiHalaqoh>  $absensiHalaqoh
     * @return array<string, int>
     */
    private function rekapPresensiHalaqoh(Collection $laporanTahfizh, Collection $absensiHalaqoh): array
    {
        $rekap = array_fill_keys(AbsensiHalaqoh::statusLabels(), 0);
        $details = $laporanTahfizh->flatMap(fn (LaporanPekananKelas $laporan) => $laporan->details);

        if ($details->isNotEmpty()) {
            foreach ($details as $detail) {
                $rekap[AbsensiHalaqoh::STATUS_HADIR] += (int) $detail->absensi_hadir;
                $rekap[AbsensiHalaqoh::STATUS_SAKIT] += (int) $detail->absensi_sakit;
                $rekap[AbsensiHalaqoh::STATUS_IZIN] += (int) $detail->absensi_izin;
                $rekap[AbsensiHalaqoh::STATUS_ALFA] += (int) $detail->absensi_alpa;
                $rekap[AbsensiHalaqoh::STATUS_TIDAK_SETOR] += (int) $detail->absensi_tidak_setor;
            }

            return $rekap;
        }

        foreach ($absensiHalaqoh as $absensi) {
            $status = $absensi->statusLabel();

            if (array_key_exists($status, $rekap)) {
                $rekap[$status]++;
            }
        }

        return $rekap;
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
