<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $santri_id
 * @property int|null $kategori_pelanggaran_id
 * @property \Illuminate\Support\Carbon $tanggal_pelanggaran
 * @property int $poin
 * @property string|null $catatan
 * @property string|null $sanksi_diberikan
 * @property string|null $status_sanksi
 */
class PelanggaranSantri extends Model
{
    protected $table = 'pelanggaran_santris';

    protected function casts(): array
    {
        return [
            'tanggal_pelanggaran' => 'date',
        ];
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class, 'santri_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriPelanggaran::class, 'kategori_pelanggaran_id');
    }
}