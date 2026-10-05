<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Laporan pekanan per mata pelajaran, READ-ONLY di WALMUR.
 *
 * CATATAN PENTING mengenai data di StudentHub: tabel `laporan_pekanans`
 * mencampur DUA bentuk baris.
 *
 *   1. Bentuk legacy (2 baris): memakai `santri_id` + kolom tahfizh
 *      (`tahfizh_*`, `it_skor`, `aqidah_skor`, ...) dengan `kelas` = '-',
 *      `mata_pelajaran` = '-', dan `periode_mulai`/`periode_selesai` NULL.
 *   2. Bentuk per-mapel (dipakai portal): memakai `setting_id`, `kelas`,
 *      `mata_pelajaran`, `semester`, `minggu_ke`, `bulan_ke`,
 *      `pekan_dalam_bulan`, `periode_mulai`, dan `periode_selesai`.
 *
 * Portal hanya membaca bentuk per-mapel, sama seperti halaman referensi.
 * Bentuk legacy diabaikan di luar cakupan MVP.
 *
 * @property int $id
 * @property int|null $setting_id
 * @property int|null $guru_id
 * @property string|null $tahun_ajaran
 * @property string|null $semester
 * @property int|null $minggu_ke
 * @property int|null $bulan_ke
 * @property int|null $pekan_dalam_bulan
 * @property \Illuminate\Support\Carbon|null $periode_mulai
 * @property \Illuminate\Support\Carbon|null $periode_selesai
 * @property string $kelas
 * @property string $mata_pelajaran
 * @property string|null $indikator
 * @property string|null $materi_pembelajaran
 * @property string|null $bulan
 * @property string|null $pekan
 */
class LaporanPekanan extends Model
{
    protected $table = 'laporan_pekanans';

    protected function casts(): array
    {
        return [
            'minggu_ke' => 'integer',
            'bulan_ke' => 'integer',
            'pekan_dalam_bulan' => 'integer',
            'periode_mulai' => 'date',
            'periode_selesai' => 'date',
            'dilewati' => 'boolean',
            'terbit_at' => 'datetime',
        ];
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(GuruMapelSetting::class, 'setting_id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function nilaiSantris(): HasMany
    {
        return $this->hasMany(NilaiLaporanPekanan::class, 'laporan_pekanan_id');
    }

    /**
     * Nama pengampu mapel, dari setting dulu baru kolom guru_id langsung.
     */
    public function getNamaGuruAttribute(): ?string
    {
        return $this->setting?->guru?->name ?? $this->guru?->name;
    }
}