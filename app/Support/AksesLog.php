<?php

namespace App\Support;

use App\Models\Santri;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

/**
 * Penulis ke `wali_access_logs`.
 *
 * Ini SATU-SATUNYA tempat WALMUR menulis ke database db_abudzarplus yang
 * dipakai bersama StudentHub. Semua tabel lain hanya dibaca.
 */
class AksesLog
{
    public static function catat(
        string $emailInput,
        ?User $user,
        bool $berhasil,
        ?Santri $santri = null,
    ): void {
        try {
            DB::table('wali_access_logs')->insert([
                'user_id' => $user?->id,
                'email_input' => mb_substr($emailInput, 0, 255),
                'santri_id' => $santri?->id,
                'berhasil' => $berhasil,
                'ip_address' => Request::ip(),
                'user_agent' => mb_substr((string) Request::userAgent(), 0, 1000),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Audit log tidak boleh menjatuhkan login wali murid: kalau tabelnya
            // belum ada atau DB sedang Bermasalah, login tetap berjalan.
            report($e);
        }
    }
}