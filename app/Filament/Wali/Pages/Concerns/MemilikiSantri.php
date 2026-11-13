<?php

namespace App\Filament\Wali\Pages\Concerns;

use App\Filament\Wali\Pages\ProfilTidakDitemukan;
use App\Models\Santri;
use Illuminate\Support\Facades\Auth;

/**
 * Resolusi profil santri milik wali murid yang sedang login.
 *
 * Sebagian kecil akun murid tidak tertaut ke baris `santris` mana pun. Akun
 * seperti itu tetap boleh login, tapi tidak boleh membuka halaman yang
 * berisi data anak - dialihkan ke layar ProfilTidakDitemukan supaya bisa
 * menghubungi pihak kesiswaan.
 */
trait MemilikiSantri
{
    private ?Santri $santriCache = null;

    private bool $santriSudahDicek = false;

    /**
     * Santri milik wali yang sedang login, atau null bila tidak tertaut.
     */
    public function connectivitySantri(): ?Santri
    {
        if ($this->santriSudahDicek) {
            return $this->santriCache;
        }

        $this->santriSudahDicek = true;
        $this->santriCache = Auth::user()?->getSantriProfile();

        return $this->santriCache;
    }

    /**
     * Sama seperti connectivitySantri(), tapi dialihkan ke layar profil
     * kosong bila akun tidak tertaut. Dipakai halaman yang butuh data anak.
     */
    public function santri(): Santri
    {
        $santri = $this->connectivitySantri();

        if ($santri === null) {
            $this->redirect(ProfilTidakDitemukan::getUrl());

            return new Santri;
        }

        return $santri;
    }
}
