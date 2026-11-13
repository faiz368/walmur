<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Header laporan pekanan tahfizh per kelas/periode.
 *
 * @property int $id
 * @property int|null $kelas_id
 * @property string $kelas_nama
 * @property Carbon $periode_mulai
 * @property Carbon $periode_selesai
 * @property string $status
 * @property Carbon|null $published_at
 * @property int|null $created_by
 * @property string|null $catatan
 */
class LaporanPekananKelas extends Model
{
    protected $table = 'laporan_pekanan_kelas';

    protected function casts(): array
    {
        return [
            'periode_mulai' => 'date',
            'periode_selesai' => 'date',
            'published_at' => 'datetime',
        ];
    }

    public function details(): HasMany
    {
        return $this->hasMany(LaporanPekananKelasDetail::class, 'laporan_pekanan_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
