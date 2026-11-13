<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Data tahfizh per santri pada satu laporan pekanan kelas.
 *
 * @property int $id
 * @property int $laporan_pekanan_id
 * @property int|null $santri_id
 * @property string $nama_santri
 * @property string|null $nis
 * @property string|null $grade
 * @property float $capaian_pekanan
 * @property float $target_pekanan
 * @property float $persentase
 * @property float $total_hafalan_juz
 * @property float $total_hafalan_halaman
 * @property int $absensi_hadir
 * @property int $absensi_sakit
 * @property int $absensi_izin
 * @property int $absensi_alpa
 * @property int $absensi_tidak_setor
 * @property string|null $catatan
 * @property int $urutan
 */
class LaporanPekananKelasDetail extends Model
{
    protected $table = 'laporan_pekanan_kelas_details';

    protected function casts(): array
    {
        return [
            'capaian_pekanan' => 'float',
            'target_pekanan' => 'float',
            'persentase' => 'float',
            'total_hafalan_juz' => 'float',
            'total_hafalan_halaman' => 'float',
            'absensi_hadir' => 'integer',
            'absensi_sakit' => 'integer',
            'absensi_izin' => 'integer',
            'absensi_alpa' => 'integer',
            'absensi_tidak_setor' => 'integer',
            'urutan' => 'integer',
        ];
    }

    public function laporanPekananKelas(): BelongsTo
    {
        return $this->belongsTo(LaporanPekananKelas::class, 'laporan_pekanan_id');
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class, 'santri_id');
    }
}
