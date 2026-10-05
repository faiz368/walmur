<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Akun `users` milik StudentHub, dipakai WALMUR secara READ-ONLY.
 *
 * Satu-satunya tulis ke database bersama yang diizinkan WALMUR adalah tabel
 * `wali_access_logs` (lihat migration create_wali_access_logs_table).
 *
 * @property int $id
 * @property string $username
 * @property string $name
 * @property string $email
 * @property string|null $role
 * @property string|null $nis
 */
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_MURID = 'murid';

    /**
     * Hanya akun murid yang boleh masuk ke panel wali murid.
     *
     * Sengaja dibandingkan dengan === 'murid', bukan "!== super_admin": dengan
     * begitu 11 role lain yang ada di database (guru, guru_walas, musyrif,
     * nakes, kesatrian, bk, pengembangan, tv, admin, super_admin, dan role
     * null) otomatis mendapat 403 tanpa didaftarkan satu per satu, dan
     * StudentHub tidak pernah membaca nilai role baru dari WALMUR.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === self::ROLE_MURID;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    /**
     * Profil santri milik akun ini.
     *
     * Berbeda dengan versi StudentHub, fallback pencarian longgar dengan
     * operator LIKE pada kolom nama SENGAJA DIHAPUS. Wali murid login hanya
     * dengan email anaknya tanpa password, jadi setiap akun wajib terikat ke
     * satu baris `santris`; pencocokan longgar berisiko menampilkan laporan
     * anak yang salah.
     *
     * Penyelesaian hanya lewat dua kunci yang deterministik:
     *   1. `santris.user_id` — tautan resmi StudentHub;
     *   2. `santris.nis`    — hanya bila cocok persis, bukan LIKE.
     *
     * `orderBy('id')` dipakai karena ada akun yang tertaut ke 2 baris
     * santris (user_id=115), sehingga tanpa itu hasilnya tidak deterministik.
     */
    public function getSantriProfile(): ?Santri
    {
        if ($this->id !== null) {
            $santri = Santri::where('user_id', $this->id)->orderBy('id')->first();

            if ($santri !== null) {
                return $santri;
            }
        }

        if (filled($this->nis)) {
            return Santri::where('nis', $this->nis)->orderBy('id')->first();
        }

        return null;
    }
}