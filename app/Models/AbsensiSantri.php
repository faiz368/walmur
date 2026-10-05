<?php

namespace App\Models;

use App\Enums\StatusAbsensi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $santri_id
 * @property int|null $kelas_id
 * @property \Illuminate\Support\Carbon $tanggal
 * @property string $status
 * @property string|null $keterangan
 */
class AbsensiSantri extends Model
{
    protected $table = 'absensi_santris';

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class, 'santri_id');
    }

    /**
     * Status ternormalisasi, menormalkan "Alpha" menjadi "Alfa".
     */
    public function statusAbsensi(): ?StatusAbsensi
    {
        return StatusAbsensi::normalize($this->status);
    }
}