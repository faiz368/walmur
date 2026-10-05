<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Diperlukan untuk resolve `LaporanPekanan::setting->guru->name`, supaya nama
 * pengampu mapel tampil di tabel KBM, dan untuk `RaporBayanganData` yang
 * menyaring mapel per kelas, tahun ajaran, dan semester.
 *
 * @property int $id
 * @property int $guru_id
 * @property string $mata_pelajaran
 * @property string|null $kelas
 * @property string|null $tahun_ajaran
 * @property string $semester
 */
class GuruMapelSetting extends Model
{
    protected $table = 'guru_mapel_settings';

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }
}
