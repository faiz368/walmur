<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Presensi halaqah tahfizh milik StudentHub, READ-ONLY di WALMUR.
 *
 * @property int $id
 * @property int $santri_id
 * @property int|null $ustadz_id
 * @property Carbon $tanggal
 * @property string $status
 * @property string|null $catatan
 */
class AbsensiHalaqoh extends Model
{
    protected $table = 'absensis';

    public const STATUS_HADIR = 'Hadir';

    public const STATUS_SAKIT = 'Sakit';

    public const STATUS_IZIN = 'Izin';

    public const STATUS_ALFA = 'Alfa';

    public const STATUS_TIDAK_SETOR = 'Tidak Setor';

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class, 'santri_id');
    }

    public function ustadz(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ustadz_id');
    }

    public function statusLabel(): string
    {
        return match (mb_strtolower(trim((string) $this->status))) {
            'hadir' => self::STATUS_HADIR,
            'sakit' => self::STATUS_SAKIT,
            'izin' => self::STATUS_IZIN,
            'alfa', 'alpha', 'alpa' => self::STATUS_ALFA,
            'tidak setor' => self::STATUS_TIDAK_SETOR,
            default => (string) $this->status,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_HADIR,
            self::STATUS_SAKIT,
            self::STATUS_IZIN,
            self::STATUS_ALFA,
            self::STATUS_TIDAK_SETOR,
        ];
    }
}
