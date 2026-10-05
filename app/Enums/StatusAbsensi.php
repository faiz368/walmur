<?php

namespace App\Enums;

enum StatusAbsensi: string
{
    case Hadir = 'Hadir';
    case Izin = 'Izin';
    case Sakit = 'Sakit';
    case Alfa = 'Alfa';

    /**
     * Data absensi di StudentHub tidak konsisten: 17 baris tertulis "Alfa"
     * dan 1 baris tertulis "Alpha". Keduanya hal yang sama, jadi dinormalkan
     * ke satu nilai enum supaya rekap tidak menghitung ganda.
     */
    public static function normalize(?string $status): ?self
    {
        if ($status === null || trim($status) === '') {
            return null;
        }

        return match (mb_strtolower(trim($status))) {
            'hadir' => self::Hadir,
            'izin' => self::Izin,
            'sakit' => self::Sakit,
            'alfa', 'alpha' => self::Alfa,
            default => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Hadir => 'Hadir',
            self::Izin => 'Izin',
            self::Sakit => 'Sakit',
            self::Alfa => 'Alfa',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function labels(): array
    {
        return array_map(fn (self $c) => $c->label(), self::cases());
    }
}