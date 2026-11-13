<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Logika grid pekanan, di-port VERBATIM dari
 * StudentHub/app/Models/LaporanPekanan.php supaya angka "Bulan / Pekan" pada
 * portal identik dengan dokumen resmi.
 *
 * Konsep: pekanan berjalan Minggu -> Sabtu. Anchor tahun ajaran adalah
 * 1 Juli tahun pertama yang dimundurkan ke Minggu terdekat, sehingga 1 Juli
 * selalu jatuh DI DALAM blok pertama dan tidak ada hari yang hilang.
 * Nomor "Bulan / Pekan" diambil dari Sabtu PENUTUP, karena laporan pekanan
 * rilis setiap Sabtu.
 */
class LaporanPekananCalculator
{
    public const NAMA_BULAN = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    /**
     * Anchor tahun ajaran: tanggal 1 Juli tahun pertama ("2026/2027" ->
     * 1 Juli 2026) dimundurkan ke Minggu terdekat.
     *
     * 1 Juli 2026 adalah Rabu, jadi anchor = 28 Juni 2026 (Minggu), dan blok
     * pertama = 28 Juni - 4 Juli 2026. Kalau 1 Juli jatuh tepat Hari Minggu,
     * tidak dimundurkan.
     */
    public static function awalTahunAjaran(?string $tahunAjaran): Carbon
    {
        $tahun = null;

        if ($tahunAjaran && preg_match('/(\d{4})/', $tahunAjaran, $match)) {
            $tahun = (int) $match[1];
        }

        $awal = Carbon::create($tahun ?? (int) date('Y'), 7, 1)->startOfDay();

        if ($awal->dayOfWeek !== Carbon::SUNDAY) {
            $awal->subDays($awal->dayOfWeek);
        }

        return $awal;
    }

    /**
     * Urutan Sabtu di dalam bulan (1..5), dihitung dari tanggal Sabtu penutup.
     * Sabtu dalam satu bulan selalu berjarak 7 hari, jadi rumus
     * ceil(hari/7) otomatis menghasilkan 1..5 sesuai jumlah Sabtu bulan itu.
     */
    public static function urutanSabtu(Carbon|string $tanggal): int
    {
        $tanggal = $tanggal instanceof Carbon ? $tanggal->copy() : Carbon::parse($tanggal);

        return (int) ceil((int) $tanggal->format('j') / 7);
    }

    /**
     * Normalisasi satu tanggal menjadi identitas pekan yang konsisten.
     *
     * Mengembalikan:
     * - minggu_ke: urutan blok sejak anchor (1..n)
     * - bulan_ke: bulan dari SABTU PENUTUP (1..12)
     * - pekan_dalam_bulan: urutan Sabtu di bulan itu (1..5)
     * - mulai/selesai: rentang kanonik (Minggu -> Sabtu)
     */
    public static function normalisasiPekan(Carbon|string $tanggal, ?string $tahunAjaran = null): array
    {
        $tanggal = $tanggal instanceof Carbon
            ? $tanggal->copy()->startOfDay()
            : Carbon::parse($tanggal)->startOfDay();

        $awal = self::awalTahunAjaran($tahunAjaran);

        // Selisih dihitung dari timestamp (bukan diffInDays) karena Carbon 3
        // membuat diffInDays() signed & float.
        $selisih = (int) round(($tanggal->getTimestamp() - $awal->getTimestamp()) / 86400);
        $mingguKe = max(1, intdiv($selisih, 7) + 1);

        $mulai = $awal->copy()->addWeeks($mingguKe - 1);
        $selesai = $mulai->copy()->addDays(6);

        return [
            'minggu_ke' => $mingguKe,
            'bulan_ke' => (int) $selesai->format('n'),
            'pekan_dalam_bulan' => self::urutanSabtu($selesai),
            'mulai' => $mulai,
            'selesai' => $selesai,
        ];
    }

    /**
     * Rentang tanggal kanonik untuk nomor pekan (Minggu -> Sabtu, 7 hari).
     *
     * @return array{0: Carbon, 1: Carbon} [periode mulai, periode selesai]
     */
    public static function periodeMingguKe(int $mingguKe, ?string $tahunAjaran = null): array
    {
        $awal = self::awalTahunAjaran($tahunAjaran);
        $mulai = $awal->copy()->addWeeks(max(0, $mingguKe - 1));

        return [$mulai, $mulai->copy()->addDays(6)];
    }

    /**
     * Inverse dari normalisasiPekan: dari "nama bulan + urutan Sabtu" mencari
     * tanggal Sabtu penutupnya, lalu dinormalisasi balik.
     *
     * Tahun ditentukan dari bulan: Juli-Desember milik tahun pertama tahun
     * ajaran, Januari-Juni milik tahun kedua ("2026/2027" + "Januari" ->
     * 2027).
     */
    public static function normalisasiDariBulanPekan(?string $bulan, ?int $pekan, ?string $tahunAjaran = null): ?array
    {
        $bulan = trim((string) $bulan);
        $pekan = (int) $pekan;

        if ($bulan === '' || $pekan < 1) {
            return null;
        }

        $bulanKe = self::bulanKeDariNama($bulan);

        if ($bulanKe === null) {
            return null;
        }

        $awal = self::awalTahunAjaran($tahunAjaran);
        $tahun = $bulanKe >= 7 ? (int) $awal->format('Y') : ((int) $awal->format('Y') + 1);

        // Sabtu pertama di bulan tersebut.
        $pertama = Carbon::create($tahun, $bulanKe, 1)->startOfDay();
        $sabtuPertama = $pertama->dayOfWeek === Carbon::SATURDAY
            ? $pertama->copy()
            : $pertama->copy()->next(Carbon::SATURDAY);

        $sabtuPenutup = $sabtuPertama->copy()->addWeeks($pekan - 1);

        return self::normalisasiPekan($sabtuPenutup, $tahunAjaran);
    }

    /**
     * Identitas periode berjalan untuk mengisi filter secara otomatis.
     *
     * Ketiganya diambil dari satu hasil normalisasiPekan() supaya semester,
     * bulan, dan pekan tidak saling bertentangan saat pekan berjalan ditutup
     * di bulan/semester berikutnya.
     *
     * @return array{semester: string, bulan: string, pekan: int, tahunAjaran: string}
     */
    public static function periodeOtomatis(?Carbon $tanggal = null): array
    {
        $tanggal ??= Carbon::now();
        $tahunAjaran = self::tahunAjaranAktif($tanggal);
        $snap = self::normalisasiPekan($tanggal, $tahunAjaran);

        return [
            'semester' => self::semesterDariBulan($snap['bulan_ke']),
            'bulan' => self::NAMA_BULAN[$snap['bulan_ke'] - 1],
            'pekan' => $snap['pekan_dalam_bulan'],
            'tahunAjaran' => $tahunAjaran,
        ];
    }

    /**
     * Nama bulan yang termasuk satu semester. Semester kosong = semua bulan.
     *
     * @return list<string>
     */
    public static function bulanUntukSemester(string $semester): array
    {
        return match ($semester) {
            '1' => array_slice(self::NAMA_BULAN, 6),
            '2' => array_slice(self::NAMA_BULAN, 0, 6),
            default => self::NAMA_BULAN,
        };
    }

    /**
     * Semua pekan yang Sabtu penutupnya jatuh di bulan tertentu.
     *
     * @return Collection<int, array>
     */
    public static function pekanUntukBulan(?string $bulan, ?string $tahunAjaran = null): Collection
    {
        $bulanKe = self::bulanKeDariNama($bulan);

        if ($bulanKe === null) {
            return collect();
        }

        return self::daftarPekan($tahunAjaran)
            ->where('bulan_ke', $bulanKe)
            ->values();
    }

    /**
     * Semua pekan dalam satu tahun ajaran, sebagai daftar opsi form.
     *
     * @return Collection<int, array>
     */
    public static function daftarPekan(?string $tahunAjaran = null): Collection
    {
        $awal = self::awalTahunAjaran($tahunAjaran);
        $daftar = collect();

        for ($i = 0; $i < 60; $i++) {
            $mulai = $awal->copy()->addWeeks($i);
            $selesai = $mulai->copy()->addDays(6);
            $bulanKe = (int) $selesai->format('n');

            // Batasi pada tahun ajaran: Juli tahun pertama s.d. Juni tahun kedua.
            if ($bulanKe < 7 && (int) $selesai->format('Y') === (int) $awal->format('Y')) {
                continue;
            }
            if ($bulanKe >= 7 && (int) $selesai->format('Y') > (int) $awal->format('Y')) {
                break;
            }

            $daftar->push([
                'minggu_ke' => $i + 1,
                'bulan_ke' => $bulanKe,
                'nama_bulan' => self::NAMA_BULAN[$bulanKe - 1],
                'pekan_dalam_bulan' => self::urutanSabtu($selesai),
                'periode_mulai' => $mulai,
                'periode_selesai' => $selesai,
            ]);
        }

        return $daftar;
    }

    /**
     * Semester mengikuti bulan dari Sabtu penutup: Juli-Desember = Ganjil (1),
     * Januari-Juni = Genap (2).
     */
    public static function semesterDariBulan(int $bulanKe): string
    {
        return $bulanKe >= 7 ? '1' : '2';
    }

    public static function namaSemester(string $semester): string
    {
        return $semester === '1' ? 'Ganjil' : 'Genap';
    }

    /**
     * "September" -> 9, nama tidak dikenal -> null.
     */
    public static function bulanKeDariNama(?string $bulan): ?int
    {
        $index = array_search(ucfirst(mb_strtolower(trim((string) $bulan))), self::NAMA_BULAN, true);

        return $index === false ? null : $index + 1;
    }

    /**
     * Tahun ajaran aktif, mis. "2026/2027". Bulan Juli ke atas milik tahun
     * pertama, Januari ke Juni milik tahun kedua.
     */
    public static function tahunAjaranAktif(?Carbon $tanggal = null): string
    {
        $tanggal ??= Carbon::now();
        $tahun = (int) $tanggal->format('Y');
        $tahunAwal = (int) $tanggal->format('n') >= 7 ? $tahun : $tahun - 1;

        return $tahunAwal.'/'.($tahunAwal + 1);
    }

    /**
     * Semester aktif dari tanggal: Juli-Desember = Ganjil, Januari-Juni = Genap.
     */
    public static function semesterAktif(?Carbon $tanggal = null): string
    {
        $tanggal ??= Carbon::now();

        return ((int) $tanggal->format('n') >= 7) ? '1' : '2';
    }
}
