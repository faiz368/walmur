<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Santri milik StudentHub, READ-ONLY di WALMUR.
 *
 * Kolom `kelas` (teks) dipakai StudentHub untuk pencocokan laporan pekanan,
 * sedangkan `kelas_id` menunjuk tabel `kelas`. Keduanya dipakai di portal:
 * `kelas` untuk query laporan, `kelas_id` untuk mencari wali kelas.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $nis
 * @property string $nama_lengkap
 * @property string|null $email
 * @property string|null $kelas
 * @property int|null $kelas_id
 * @property string|null $angkatan
 * @property string|null $asrama
 * @property string|null $halaqoh
 * @property string|null $jenis_kelamin
 * @property string|null $status
 */
class Santri extends Model
{
    use HasFactory;

    protected $table = 'santris';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function kelasInfo(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function absensis(): HasMany
    {
        return $this->hasMany(AbsensiSantri::class, 'santri_id');
    }

    public function sakits(): HasMany
    {
        return $this->hasMany(SantriSakit::class, 'santri_id');
    }

    public function izins(): HasMany
    {
        return $this->hasMany(SantriIzin::class, 'santri_id');
    }

    public function pelanggaran(): HasMany
    {
        return $this->hasMany(PelanggaranSantri::class, 'santri_id');
    }

    public function tesHafalans(): HasMany
    {
        return $this->hasMany(TesHafalan::class, 'santri_id');
    }

    public function nilaiLaporanPekanans(): HasMany
    {
        return $this->hasMany(NilaiLaporanPekanan::class, 'santri_id');
    }
}