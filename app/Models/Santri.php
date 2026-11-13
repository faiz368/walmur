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
 * @property int|null $halaqah_id
 * @property string|null $target_juz
 * @property string|null $current_juz
 * @property string|null $total_surah_hafal
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

    /**
     * Nama kelas santri.
     *
     * Tabel `santris` di database bersama tidak punya kolom `kelas` - hanya
     * `kelas_id` yang menunjuk tabel `kelas`. StudentHub memakai accessor di
     * modelnya (StudentHub/app/Models/Santri.php `getKelasAttribute`) untuk
     * menerjemahkan id itu menjadi teks "Kelas VII A", dst; WALMUR meniru
     * perilaku yang sama supaya query `guru_mapel_settings.kelas` (yang
     * memakai teks nama kelas) dan seluruh cetakan rapor/laporan cocok.
     *
     * Hasil di-cache per id supaya tidak N+1 saat tabel rapor memakai
     * `$santri->kelas` berulang kali dalam satu request.
     *
     * @var array<int, string|null>
     */
    private static array $kelasNamaCache = [];

    public function getKelasAttribute($value = null): ?string
    {
        if (filled($value)) {
            return $value;
        }

        $kelasId = $this->kelas_id;

        if (blank($kelasId)) {
            return null;
        }

        if (! array_key_exists($kelasId, self::$kelasNamaCache)) {
            self::$kelasNamaCache[$kelasId] = Kelas::query()
                ->whereKey($kelasId)
                ->value('nama_kelas');
        }

        return self::$kelasNamaCache[$kelasId];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function kelasInfo(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function halaqah(): BelongsTo
    {
        return $this->belongsTo(Halaqah::class, 'halaqah_id');
    }

    public function setorans(): HasMany
    {
        return $this->hasMany(Setoran::class, 'santri_id');
    }

    public function absensiHalaqohs(): HasMany
    {
        return $this->hasMany(AbsensiHalaqoh::class, 'santri_id');
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

    public function laporanPekananKelasDetails(): HasMany
    {
        return $this->hasMany(LaporanPekananKelasDetail::class, 'santri_id');
    }
}
