@php
    use App\Filament\Wali\Pages\LaporanPekanan;
@endphp

<x-filament-panels::page>
    {{-- Header Dashboard --}}
    <div class="flex items-center justify-between gap-3 border-b border-gray-200/80 pb-3 mb-6">
        <div class="min-w-0">
            <h1 class="text-base font-extrabold uppercase tracking-tight text-navy-900 sm:text-lg">Pondok Tahfizh Plus Abu Dzar</h1>
            <p class="text-[11px] font-bold uppercase tracking-wide text-olive-600 sm:text-xs">Bidang Kesiswaan &amp; Kesantrian</p>
        </div>

        <div class="hidden shrink-0 text-right text-xs text-gray-500 sm:block">
            <span class="inline-block rounded-full border border-olive-200 bg-olive-50 px-3 py-1 text-center font-extrabold leading-tight text-olive-700">
                Laporan Resmi<br>Wali Santri
            </span>
        </div>
    </div>

    {{-- Filter periode --}}
    <div class="mb-6 space-y-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h4 class="wali-section-title">
                <x-heroicon-m-funnel class="h-4 w-4 text-olive-500" />
                <span>Periode Laporan</span>
            </h4>

            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-bold text-emerald-700">
                    @if (blank($semester) && blank($bulan) && blank($pekan))
                        Semua Periode
                    @else
                        Semester {{ $periode['semester'] === '1' ? 'Ganjil' : ($periode['semester'] === '2' ? 'Genap' : 'Semua') }}
                        @if ($periode['bulan']) &bull; {{ $periode['bulan'] }} @endif
                        @if ($periode['pekan']) (Pekan {{ $periode['pekan'] }}) @endif
                    @endif
                </span>

                <x-filament::button type="button" size="xs" color="gray" outlined
                    icon="heroicon-m-arrow-path" wire:click="pilihPeriodeSekarang">
                    Sekarang
                </x-filament::button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="mb-1 block font-bold text-gray-700" for="wali-semester">Semester</label>
                <select id="wali-semester" wire:model.live="semester"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 font-bold focus:border-olive-500">
                    <option value="">Semua Semester</option>
                    <option value="1">Semester Ganjil (1)</option>
                    <option value="2">Semester Genap (2)</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block font-bold text-gray-700" for="wali-bulan">Bulan</label>
                <select id="wali-bulan" wire:model.live="bulan"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 font-bold focus:border-olive-500">
                    <option value="">Semua Bulan</option>
                    @foreach ($pilihanBulan as $nama)
                        <option value="{{ $nama }}">{{ $nama }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block font-bold text-gray-700" for="wali-pekan">Pekan ke-</label>
                <select id="wali-pekan" wire:model.live="pekan" @disabled(blank($bulan))
                    title="{{ blank($bulan) ? 'Pilih bulan terlebih dahulu' : '' }}"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 font-bold focus:border-olive-500 disabled:cursor-not-allowed disabled:opacity-60">
                    <option value="">Semua Pekan</option>
                    @foreach ($daftarPekan as $p)
                        <option value="{{ $p['pekan_dalam_bulan'] }}">
                            {{ $p['nama_bulan'] }} / Pekan {{ $p['pekan_dalam_bulan'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <x-filament::button tag="a" size="sm"
                    href="{{ LaporanPekanan::getUrl([
                        'semester' => $semester,
                        'bulan' => $bulan,
                        'pekan' => $pekan,
                    ]) }}" icon="heroicon-m-document-text" class="w-full">
                    Lihat Laporan Lengkap
                </x-filament::button>
            </div>
        </div>
    </div>

    {{-- Identitas anak --}}
    <div class="wali-identitas mb-6 text-xs">
        <div>
            <span class="wali-identitas-label">Nama Lengkap Santri</span>
            <h4 class="wali-identitas-nilai text-olive-400 break-words">{{ $santri->nama_lengkap }}</h4>
        </div>
        <div>
            <span class="wali-identitas-label">NIS / Nomor Induk</span>
            <h4 class="wali-identitas-nilai font-mono">{{ $santri->nis ?? '-' }}</h4>
        </div>
        <div>
            <span class="wali-identitas-label">Kelas &amp; Angkatan</span>
            <h4 class="wali-identitas-nilai">
                {{ $santri->kelas ?? '-' }} (Angkatan {{ $santri->angkatan ?? '-' }})
            </h4>
        </div>
        <div>
            <span class="wali-identitas-label">Asrama / Kelompok</span>
            <h4 class="wali-identitas-nilai">{{ $santri->asrama ?? 'Komplek Utama' }}</h4>
        </div>
    </div>

    {{-- Rekap kehadiran --}}
    <div class="mb-6 space-y-3">
        <h4 class="wali-section-title border-b border-gray-200 pb-2">
            <x-heroicon-m-chart-pie class="h-4 w-4 text-olive-500" />
            <span>Rekapitulasi Kehadiran &amp; Kesehatan (Periode Ini)</span>
        </h4>

        <div class="grid grid-cols-2 gap-3 text-xs sm:grid-cols-4">
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-3.5 text-center">
                    <span class="block text-[11px] font-extrabold uppercase tracking-wider text-emerald-700">Total Hadir</span>
                    <h3 class="mt-1 text-2xl font-extrabold text-emerald-800">{{ $cntHadir }}
                        <span class="text-xs font-bold text-emerald-700">Hari</span>
                    </h3>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-3.5 text-center">
                    <span class="block text-[11px] font-extrabold uppercase tracking-wider text-amber-700">Total Sakit</span>
                    <h3 class="mt-1 text-2xl font-extrabold text-amber-800">{{ $cntSakit }}
                        <span class="text-xs font-bold text-amber-700">Kejadian</span>
                    </h3>
                </div>
                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-3.5 text-center">
                    <span class="block text-[11px] font-extrabold uppercase tracking-wider text-blue-700">Total Izin</span>
                    <h3 class="mt-1 text-2xl font-extrabold text-blue-800">{{ $cntIzin }}
                        <span class="text-xs font-bold text-blue-700">Kejadian</span>
                    </h3>
                </div>
                <div class="rounded-2xl border border-rose-200 bg-rose-50 p-3.5 text-center">
                    <span class="block text-[11px] font-extrabold uppercase tracking-wider text-rose-700">Total Alfa</span>
                    <h3 class="mt-1 text-2xl font-extrabold text-rose-800">{{ $cntAlfa }}
                        <span class="text-xs font-bold text-rose-700">Hari</span>
                    </h3>
                </div>
            </div>

        @if ($jumlahAbsensiTercatat === 0)
            <p class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-center text-xs font-semibold text-gray-500">
                Belum ada data kehadiran yang tercatat pada periode ini.
            </p>
        @endif
    </div>

    {{-- Wali kelas --}}
    <div class="mb-6 rounded-2xl border border-gray-200 bg-gray-50 p-4 text-xs">
        <h4 class="mb-2 font-bold text-gray-900">Wali Kelas</h4>
        <p class="text-gray-700">
            {{ $waliKelas['nama'] ?? 'Belum ditentukan' }}
            @if (! empty($waliKelas['nip']))
                <span class="ml-1 text-[10px] text-gray-400">NIP / ID: {{ $waliKelas['nip'] }}</span>
            @endif
        </p>
        <p class="mt-1 text-gray-500">
            Kelompok halaqah: <span class="font-bold text-gray-700">{{ $santri->halaqoh ?? '-' }}</span>
        </p>
    </div>

    {{-- Catatan --}}
    <div class="space-y-1 rounded-2xl border border-gray-200 bg-gray-50 p-4 text-xs">
        <h5 class="font-bold text-gray-900">Catatan:</h5>
        <p class="italic leading-relaxed text-gray-600">
            &ldquo;Mohon bapak/ibu wali santri untuk senantiasa mendoakan dan memberikan
            motivasi kepada ananda agar selalu semangat dalam menuntut ilmu, menjaga
            kesehatan, serta mematuhi tata tertib Pondok Tahfizh Plus Abu Dzar.&rdquo;
        </p>
    </div>
</x-filament-panels::page>