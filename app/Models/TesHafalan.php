<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $santri_id
 * @property bool $is_lulus
 * @property string|null $nilai
 * @property int|null $nnilai_angka
 * @property string|null $juz
 */
class TesHafalan extends Model
{
    protected $table = 'tes_hafalans';

    protected function casts(): array
    {
        return [
            'is_lulus' => 'boolean',
            'nnilai_angka' => 'integer',
        ];
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class, 'santri_id');
    }
}