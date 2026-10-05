<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $nama_kelas
 * @property string|null $tingkat
 * @property float|null $tingkat_kelas
 * @property int|null $wali_kelas_id
 * @property string|null $wali_kelas_nama
 * @property string|null $wali_kelas_nip
 * @property string|null $ttd_walas
 */
class Kelas extends Model
{
    protected $table = 'kelas';

    /**
     * Menentukan SMP atau SMA dari nama kelas, untuk memilih kop surat,
     * kepala sekolah, dan setting rapor yang sesuai.
     *
     * Diport dari StudentHub/app/Models/Kelas.php. Nama kelas yang ada di
     * produksi berformat romawi yang dipisah spasi ("IX B", "Kelas X A",
     * "XII Grade A"), sehingga pola di bawah sudah cukup.
     */
    public static function detectTingkat(?string $namaKelas): string
    {
        if (! $namaKelas) {
            return 'SMP';
        }

        $upper = strtoupper($namaKelas);

        if (str_contains($upper, 'SMA')) {
            return 'SMA';
        }

        if (str_contains($upper, 'SMP')) {
            return 'SMP';
        }

        if (preg_match('/\b(X|XI|XII|10|11|12)\b/i', $namaKelas)) {
            return 'SMA';
        }

        if (preg_match('/\b(VII|VIII|IX|7|8|9)\b/i', $namaKelas)) {
            return 'SMP';
        }

        return 'SMP';
    }

    public function isSma(): bool
    {
        return static::detectTingkat($this->nama_kelas) === 'SMA';
    }

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wali_kelas_id');
    }
}
