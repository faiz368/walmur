<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Asesmen sumatif perereotype(mapel) per santri.
 *
 * Rapor Bayangan (STS) hanya memakai kolom `nilai_sts`. Kolom LM1..LM10,
 * `rata_lm`, `nilai_sas`, dan `nilai_akhir` sengaja tidak dipakai supaya angka
 * yang tampil sama persis dengan StudentHub RaporController@cetakBayangan,
 * yang memang "Ambil Nilai Murni STS per Mapel (tanpa akumulasi bobot)".
 *
 * @property int $id
 * @property int $santri_id
 * @property int $setting_id
 * @property float|null $nilai_sts
 * @property float|null $rata_lm
 * @property float|null $nilai_sas
 */
class NilaiSumatif extends Model
{
    protected $table = 'nilai_sumatifs';

    protected function casts(): array
    {
        return [
            'nilai_sts' => 'float',
            'rata_lm' => 'float',
            'nilai_sas' => 'float',
        ];
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(GuruMapelSetting::class, 'setting_id');
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class, 'santri_id');
    }
}
