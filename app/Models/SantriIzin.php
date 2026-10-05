<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $santri_id
 * @property \Illuminate\Support\Carbon $tanggal_mulai
 * @property \Illuminate\Support\Carbon $tanggal_akhir
 * @property string|null $alasan
 * @property string|null $status_kepentingan
 * @property string|null $tujuan
 * @property string|null $status
 * @property string|null $jam_kembali_rencana
 * @property string|null $jam_masuk
 */
class SantriIzin extends Model
{
    protected $table = 'santri_izins';

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_akhir' => 'date',
            'tanggal_keluar' => 'date',
            'tanggal_kembali' => 'date',
        ];
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class, 'santri_id');
    }

    /**
     * Kategori kepulangan: Belum Kembali / Tepat Waktu / Terlambat.
     */
    public function getKategoriKepulanganAttribute(): string
    {
        if ($this->status !== 'Kembali') {
            return 'Belum Kembali';
        }

        $aktual = $this->tanggal_kembali?->format('Y-m-d').' '.($this->jam_masuk ?? '00:00');
        $rencana = $this->tanggal_akhir?->format('Y-m-d').' '.($this->jam_kembali_rencana ?? '23:59');

        return strtotime($aktual) <= strtotime($rencana) ? 'Tepat Waktu' : 'Terlambat';
    }
}