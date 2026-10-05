<?php

namespace App\Support;

use App\Models\CatatanWaliKelas;
use App\Models\GuruMapelSetting;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\NilaiSumatif;
use App\Models\Santri;
use App\Models\SettingRapor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Menyusun dokumen Rapor Bayangan (STS) untuk satu santri dari database
 * baca-saja.
 *
 * Query disalin dari StudentHub/app/Http/Controllers/RaporController.php
 * (method `cetakBayangan`, baris 266-339) supaya angka yang tampil di portal
 * ini sama dengan rapor resmi yang dicetak dari StudentHub.
 *
 * Bedanya yang disengaja:
 *   - `SettingRapor` memakai `first()` + nilai bawaan, bukan `firstOrCreate()`,
 *     karena WALMUR tidak boleh menulis ke database bersama.
 *   - `tahun_ajaran` diambil dari tahun ajaran TERAKHIR yang benar-benar punya
 *     mapel di kelas anak, bukan sekadar tahun ajaran terakhir di database,
 *     supaya tabel rapor tidak diam-diam kosong.
 */
class RaporBayanganData
{
    public function __construct(private readonly Santri $santri) {}

    public static function untuk(Santri $santri): self
    {
        return new self($santri);
    }

    /**
     * @return array{
     *     tahun: ?string,
     *     semester: string,
     *     mapelSettings: Collection,
     *     nilaiPerMapel: array<int, float|null>,
     *     kkmPerMapel: array<string, int>,
     *     catatan: ?CatatanWaliKelas,
     *     waliKelasNama: ?string,
     *     waliKelasNip: ?string,
     *     ttdWalas: ?string,
     *     kepsekNama: string,
     *     kepsekNiy: ?string,
     *     kepsekTtd: ?string,
     *     tingkat: string
     * }
     */
    public function untukSemester(string $semester): array
    {
        $semester = $semester === '2' ? '2' : '1';
        $tingkat = $this->tingkat();

        $tahun = $this->tahunAjaranTerakhir($semester);

        $mapelSettings = filled($this->santri->kelas) && $tahun !== null
            ? GuruMapelSetting::query()
                ->where('kelas', $this->santri->kelas)
                ->where('tahun_ajaran', $tahun)
                ->where('semester', $semester)
                ->with('guru')
                ->orderBy('mata_pelajaran')
                ->get()
            : new Collection;

        return [
            'tahun' => $tahun,
            'semester' => $semester,
            'tingkat' => $tingkat,
            'mapelSettings' => $mapelSettings,
            'nilaiPerMapel' => $this->nilaiPerMapel($mapelSettings),
            'kkmPerMapel' => $this->kkmPerMapel(),
            'catatan' => $tahun === null
                ? null
                : CatatanWaliKelas::query()
                    ->where('santri_id', $this->santri->id)
                    ->where('tahun_ajaran', $tahun)
                    ->where('semester', $semester)
                    ->bayangan()
                    ->first(),
            ...$this->ttdDanNama(),
        ];
    }

    /**
     * Nilai STS per mapel, tanpa akumulasi bobot.
     *
     * `nilai_sumatifs` punya unique (santri_id, setting_id), jadi `keyBy` aman
     * dan tidak mungkin menimpa nilai baris lain.
     *
     * @param  Collection<int, GuruMapelSetting>  $mapelSettings
     * @return array<int, float|null>
     */
    private function nilaiPerMapel(Collection $mapelSettings): array
    {
        if ($mapelSettings->isEmpty()) {
            return [];
        }

        $sumatif = NilaiSumatif::query()
            ->where('santri_id', $this->santri->id)
            ->whereIn('setting_id', $mapelSettings->pluck('id'))
            ->get()
            ->keyBy('setting_id');

        $nilai = [];

        foreach ($mapelSettings as $setting) {
            $nilai[$setting->id] = $sumatif->get($setting->id)?->nilai_sts;
        }

        return $nilai;
    }

    /**
     * KKM dicocokkan lewat nama mapel. Sumber aslinya menulis
     * `where('kkm','>',0)->orWhereNotNull('kkm')` yang secara logika sama saja
     * dengan `whereNotNull('kkm')`, jadi disederhanakan di sini.
     *
     * @return array<string, int>
     */
    private function kkmPerMapel(): array
    {
        return MataPelajaran::query()
            ->whereNotNull('kkm')
            ->pluck('kkm', 'nama')
            ->all();
    }

    /**
     * Tahun ajaran terakhir yang benar-benar punya mapel di kelas anak ini
     * UNTUK SEMESTER YANG DIPILIH.
     *
     * Semester ikut diperhitungkan karena `guru_mapel_settings` diisi per
     * semester: kelas yang baru mulai memakai sistem rapor bisa punya
     * tahun ajaran termuda yang isinya semester 2 saja. Kalau semester
     * diabaikan, rapor Ganjil akan memakai tahun itu lalu menampilkan
     * tabel kosong.
     */
    private function tahunAjaranTerakhir(string $semester): ?string
    {
        if (blank($this->santri->kelas)) {
            return null;
        }

        $tahun = GuruMapelSetting::query()
            ->where('kelas', $this->santri->kelas)
            ->where('semester', $semester)
            ->whereNotNull('tahun_ajaran')
            ->distinct()
            ->pluck('tahun_ajaran')
            ->sort()
            ->last();

        if ($tahun !== null) {
            return (string) $tahun;
        }

        return LaporanPekananCalculator::tahunAjaranAktif();
    }

    /**
     * @return array{waliKelasNama: ?string, waliKelasNip: ?string, ttdWalas: ?string, kepsekNama: string, kepsekNiy: ?string, kepsekTtd: ?string}
     */
    private function ttdDanNama(): array
    {
        $kelas = $this->kelas();

        $setting = SettingRapor::first();
        $tingkat = $this->tingkat();
        $prefix = $tingkat === 'SMA' ? 'kepsek_sma' : 'kepsek_smp';

        return [
            'waliKelasNama' => $kelas?->wali_kelas_nama ?: $kelas?->waliKelas?->name,
            'waliKelasNip' => $kelas?->wali_kelas_nip,
            'ttdWalas' => $kelas?->ttd_walas,
            'kepsekNama' => $setting?->{$prefix.'_nama'} ?: SettingRapor::BAWAAN[$prefix.'_nama'],
            'kepsekNiy' => $setting?->{$prefix.'_niy'} ?: SettingRapor::BAWAAN[$prefix.'_niy'],
            'kepsekTtd' => $setting?->{$prefix.'_ttd'},
        ];
    }

    /**
     * Baris kelas diambil dari nama kelas pada Santri, sama seperti
     * StudentHub yang melakukan DB::table("kelas")->where("nama_kelas")...
     */
    private function kelas(): ?Kelas
    {
        if (blank($this->santri->kelas)) {
            return null;
        }

        return Kelas::query()->where('nama_kelas', $this->santri->kelas)->first();
    }

    private function tingkat(): string
    {
        return Kelas::detectTingkat($this->santri->kelas);
    }

    /**
     * Tanggal cetak di blok tanda tangan.
     */
    public function tanggalCetak(): Carbon
    {
        return Carbon::now();
    }
}
