<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan tanda tangan kepala sekolah untuk rapor, dipisah antara SMP dan
 * SMA karena keduanya kepala sekolah yang berbeda.
 *
 * PENTING: StudentHub memakai `SettingRapor::getSetting()` yang memanggil
 * `firstOrCreate()`. Di WALMUR itu DILARANG karena `wali_access_logs` adalah
 * satu-satunya tabel yang boleh ditulis di database bersama ini - makanya di
 * sini hanya `first()`, dan pemanggil wajib menangani baris yang belum ada.
 *
 * @property int $id
 * @property string|null $kepsek_smp_nama
 * @property string|null $kepsek_smp_niy
 * @property string|null $kepsek_smp_ttd
 * @property string|null $kepsek_sma_nama
 * @property string|null $kepsek_sma_niy
 * @property string|null $kepsek_sma_ttd
 */
class SettingRapor extends Model
{
    protected $table = 'setting_rapors';

    /**
     * Nilai bawaan yang dipakai StudentHub saat baris pengaturan belum dibuat.
     * Disalin supaya tampilan portal tetap sama meskipun `setting_rapors`
     * masih kosong - tanpa menulis apa pun ke database.
     */
    public const BAWAAN = [
        'kepsek_smp_nama' => 'Ust. Akbar Pangestu, S.Pd',
        'kepsek_smp_niy' => '19880512 201201 1 002',
        'kepsek_sma_nama' => 'Ust. M. Ghoffar, M.Pd',
        'kepsek_sma_niy' => '19880512 201201 1 002',
    ];
}
