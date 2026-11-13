<?php

namespace App\Filament\Wali\Pages;

use App\Filament\Wali\Pages\Concerns\MemilikiSantri;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * Layar untuk akun murid yang login berhasil tetapi tidak tertaut ke
 * baris `santris` mana pun, sehingga tidak ada laporan yang bisa ditampilkan.
 *
 * Ditampilkan, bukan 404, supaya wali tahu masalahnya ada di sisi data
 * sekolah (akun belum ditautkan) - bukan karena mereka salah mengetik.
 */
class ProfilTidakDitemukan extends Page
{
    use MemilikiSantri;

    protected static ?string $title = 'Profil Belum Terhubung';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $slug = 'profil-tidak-ditemukan';

    protected string $view = 'filament.wali.pages.profil-tidak-ditemukan';

    /**
     * Halaman ini hanya reached lewat pengalihan dari trait MemilikiSantri,
     * jadi tidak boleh muncul sebagai menu untuk wali yang profilnya sudah
     * tertaut.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getViewData(): array
    {
        $user = Auth::user();

        return [
            'namaAkun' => $user?->name,
            'emailAkun' => $user?->email,
            'adaProfil' => $this->connectivitySantri() !== null,
        ];
    }
}
