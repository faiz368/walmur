<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Alias Portal Wali Murid
|--------------------------------------------------------------------------
|
| Menyamakan pintu masuk WALMUR dengan halaman yang sudah dikenal wali
| murid di StudentHub, sehingga tidak perlu sosialisasi URL baru.
|
| Path ditulis langsung (bukan Page::getUrl()) karena routes/web.php
| dievaluasi sebelum route panel Filament didaftarkan.
|
*/

Route::redirect('/', '/wali')->name('beranda');

Route::redirect('/laporan-ortu/wali', '/wali')
    ->name('laporan-ortu.ortu-masuk');

Route::redirect('/laporan-ortu/wali/laporan', '/wali/laporan-pekanan')
    ->name('laporan-ortu.ortu-laporan');

Route::redirect('/profil-tidak-ditemukan', '/wali/profil-tidak-ditemukan')
    ->name('profil-tidak-ditemukan');