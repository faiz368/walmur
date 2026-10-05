<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Master mata pelajaran. Dipakai Rapor Bayangan hanya untuk mengambil KKM,
 * karena tiap mapel punya KKM sendiri-sendiri.
 *
 * KKM dicocokkan lewat kolom `nama` (bukan id), sama seperti StudentHub
 * RaporController@cetakBayangan.
 *
 * @property int $id
 * @property string $nama
 * @property string|null $kode
 * @property string|null $kelompok
 * @property int|null $kkm
 * @property bool $is_active
 */
class MataPelajaran extends Model
{
    protected $table = 'mata_pelajarans';

    protected function casts(): array
    {
        return [
            'kkm' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
