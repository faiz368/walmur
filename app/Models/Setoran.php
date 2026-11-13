<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Setoran tahfizh milik StudentHub, READ-ONLY di WALMUR.
 *
 * @property int $id
 * @property int $santri_id
 * @property int|null $ustadz_id
 * @property string $jenis_setoran
 * @property string $juz
 * @property string $surah_name
 * @property int $ayat_awal
 * @property int $ayat_akhir
 * @property string $nilai
 * @property float|null $nilai_angka
 * @property string|null $nilai_predikat
 * @property string $absensi
 * @property string|null $catatan
 * @property Carbon $tanggal
 * @property string $tahun_ajaran
 * @property string $semester
 * @property string|null $kategori_murajaah
 * @property string|null $porsi_murajaah
 * @property int|null $jumlah_halaman_murajaah
 */
class Setoran extends Model
{
    protected $table = 'setorans';

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nilai_angka' => 'float',
            'jumlah_halaman_murajaah' => 'integer',
        ];
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class, 'santri_id');
    }

    public function ustadz(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ustadz_id');
    }

    public function scopeZiyadah(Builder $query): Builder
    {
        return $query->where('jenis_setoran', 'Ziyadah');
    }

    public function scopeMurojaah(Builder $query): Builder
    {
        return $query->whereIn('jenis_setoran', ['Murajaah', 'Murajaah30Juz']);
    }
}
