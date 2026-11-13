<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kelompok halaqah tahfizh milik StudentHub, READ-ONLY di WALMUR.
 *
 * @property int $id
 * @property string $nama_halaqah
 * @property int|null $ustadz_id
 * @property string|null $deskripsi
 * @property int $urutan
 */
class Halaqah extends Model
{
    protected $table = 'halaqahs';

    public function ustadz(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ustadz_id');
    }

    public function santris(): HasMany
    {
        return $this->hasMany(Santri::class, 'halaqah_id');
    }
}
