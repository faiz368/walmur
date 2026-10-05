<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $santri_id
 * @property \Illuminate\Support\Carbon|null $tanggal_sakit
 * @property string|null $keluhan
 * @property string|null $penanganan
 * @property string|null $status_perawatan
 * @property string|null $diagnosis
 * @property string|null $kondisi
 */
class SantriSakit extends Model
{
    protected $table = 'santri_sakits';

    protected function casts(): array
    {
        return [
            'tanggal_sakit' => 'date',
            'tanggal_mulai' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class, 'santri_id');
    }
}