<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan wali kelas per santri per periode, untuk Rapor Bayangan (STS) maupun
 * Rapor Semester (PAS/SAS). Rapor Bayangan hanya membaca `jenis_rapor` =
 * 'Bayangan'.
 *
 * Kolom `sakit`/`izin`/`alpha` di sini adalah angka yang sudah diinput wali
 * kelas, BUKAN hasil hitungan ulang dari tabel absensi. Itu sebabnya portal
 * ini menampilkan apa adanya supaya tidak berbeda dari rapor yang dicetak.
 *
 * @property int $id
 * @property int $santri_id
 * @property string $tahun_ajaran
 * @property string $semester
 * @property string $jenis_rapor
 * @property string|null $catatan_akademik
 * @property string|null $catatan_kepribadian
 * @property string|null $perilaku
 * @property string|null $kedisiplinan
 * @property string|null $kebersihan
 * @property int $sakit
 * @property int $izin
 * @property int $alpha
 * @property string|null $keputusan
 */
class CatatanWaliKelas extends Model
{
    public const JENIS_BAYANGAN = 'Bayangan';

    public const JENIS_SEMESTER = 'Semester';

    protected $table = 'catatan_wali_kelases';

    protected function casts(): array
    {
        return [
            'sakit' => 'integer',
            'izin' => 'integer',
            'alpha' => 'integer',
        ];
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class, 'santri_id');
    }

    public function scopeBayangan(Builder $query): Builder
    {
        return $query->where('jenis_rapor', self::JENIS_BAYANGAN);
    }
}
