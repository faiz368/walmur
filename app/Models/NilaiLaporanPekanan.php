<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Nilai pekanan per santri. Indikatornya milik laporan induk; yang berbeda
 * antar anak hanya `nilai`.
 *
 * @property int $id
 * @property int $laporan_pekanan_id
 * @property int $santri_id
 * @property float|null $nilai
 */
class NilaiLaporanPekanan extends Model
{
    protected $table = 'laporan_pekanan_nilai';

    protected function casts(): array
    {
        return [
            'nilai' => 'float',
        ];
    }

    public function laporanPekanan(): BelongsTo
    {
        return $this->belongsTo(LaporanPekanan::class, 'laporan_pekanan_id');
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class, 'santri_id');
    }
}