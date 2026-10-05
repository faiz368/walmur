<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $nama
 * @property string $tingkat
 * @property int $poin
 * @property string|null $sanksi_default
 */
class KategoriPelanggaran extends Model
{
    protected $table = 'kategori_pelanggarans';

    public function pelanggaran(): HasMany
    {
        return $this->hasMany(PelanggaranSantri::class, 'kategori_pelanggaran_id');
    }
}