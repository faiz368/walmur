@php
    use App\Filament\Wali\Pages\Dashboard;
@endphp

<x-filament-panels::page>
    {{-- Toolbar cetak --}}
    <div class="wali-toolbar-cetak mb-6 flex flex-col items-center justify-between gap-4 rounded-2xl border border-navy-800 bg-navy-900 p-4 text-white shadow-xl sm:flex-row">
        <div class="flex items-center gap-3">
            <x-heroicon-m-arrow-left-circle class="h-6 w-6 text-slate-300" />
            <div>
                <h3 class="text-sm font-bold text-slate-100">
                    Laporan Pekanan Wali Santri: {{ $santri->nama_lengkap }}
                </h3>
                <p class="text-xs text-slate-300">
                    Periode {{ $tanggalMulai }} &mdash; {{ $tanggalSelesai }}
                </p>
            </div>
        </div>

        <div class="flex w-full items-center gap-3 sm:w-auto">
            <x-filament::button tag="a" size="sm" color="primary" icon="heroicon-m-printer"
                x-on:click="window.print()" class="flex-1 sm:flex-none">
                Cetak / Simpan PDF
            </x-filament::button>
            <x-filament::button tag="a" size="sm" color="gray" outlined icon="heroicon-m-arrow-left"
                href="{{ Dashboard::getUrl() }}" class="flex-1 sm:flex-none">
                Kembali
            </x-filament::button>
        </div>
    </div>

    {{-- Filter. Tab rapor hanya punya semester, filter periode tidak berlaku --}}
    @if ($tabAktif === 'rapor')
        <div class="mb-6 space-y-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-4">
                <div>
                    <label class="mb-1 block font-bold text-gray-700" for="lp-semester-rapor">Semester</label>
                    <select id="lp-semester-rapor" wire:model.live="semesterRapor"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 font-bold focus:border-olive-500">
                        <option value="1">Semester Ganjil (1)</option>
                        <option value="2">Semester Genap (2)</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <span class="w-full rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-center text-[10px] font-bold text-emerald-700">
                        {{ $rapor['tahun'] ?? '-' }} &bull; {{ count($rapor['mapelSettings'] ?? []) }} Mapel
                    </span>
                </div>
            </div>

            <p class="text-[11px] leading-relaxed text-gray-500">
                Rapor Bayangan memakai <strong>nilai STS murni per mata pelajaran</strong>,
                tanpa akumulasi bobot. Tahun ajaran dan semester di atas hanya memilih
                periode rapor; filter bulan dan pekan di tab lain tidak berlaku di sini.
            </p>
        </div>
    @else
    <div class="mb-6 space-y-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-4">
            <div>
                <label class="mb-1 block font-bold text-gray-700" for="lp-semester">Semester</label>
                <select id="lp-semester" wire:model.live="semester"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 font-bold focus:border-olive-500">
                    <option value="">Semua Semester</option>
                    <option value="1">Semester Ganjil (1)</option>
                    <option value="2">Semester Genap (2)</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block font-bold text-gray-700" for="lp-bulan">Bulan</label>
                <select id="lp-bulan" wire:model.live="bulan"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 font-bold focus:border-olive-500">
                    <option value="">Semua Bulan</option>
                    @foreach ($namaBulan as $nama)
                        <option value="{{ $nama }}">{{ $nama }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block font-bold text-gray-700" for="lp-pekan">Pekan ke-</label>
                <select id="lp-pekan" wire:model.live="pekan"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 font-bold focus:border-olive-500">
                    <option value="">Semua Pekan</option>
                    @foreach ($daftarPekan as $p)
                        <option value="{{ $p['pekan_dalam_bulan'] }}">
                            {{ $p['nama_bulan'] }} / Pekan {{ $p['pekan_dalam_bulan'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
<span class="w-full rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-center text-[10px] font-bold text-emerald-700">
                        {{ count($kbmLaporans) }} Mapel &bull; {{ count($absensis) }} Absensi
                    </span>
                </div>
            </div>
        </div>
    @endif

    {{-- Pemilih tab. Diletakkan di luar dokumen supaya tetap bisa berpindah
         tab saat dokumen Rapor Bayangan yang sedang tampil --}}
    <div class="wali-tab-head no-print mb-4 overflow-x-auto" style="-webkit-overflow-scrolling:touch;scrollbar-width:none;">
        <button type="button" wire:click="$set('tabAktif', 'kbm')"
            @class(['wali-tab', $tabAktif === 'kbm' ? 'wali-tab-aktif' : 'wali-tab-nonaktif'])>
            <x-heroicon-m-book-open class="h-4 w-4" />
            <span>1. KBM (Mata Pelajaran)</span>
        </button>
        <button type="button" wire:click="$set('tabAktif', 'kesiswaan')"
            @class(['wali-tab', $tabAktif === 'kesiswaan' ? 'wali-tab-aktif' : 'wali-tab-nonaktif'])>
            <x-heroicon-m-shield-check class="h-4 w-4" />
            <span>2. Kesiswaan &amp; Kesantrian</span>
        </button>
        <button type="button" wire:click="$set('tabAktif', 'tahfizh')"
            @class(['wali-tab', $tabAktif === 'tahfizh' ? 'wali-tab-aktif' : 'wali-tab-nonaktif'])>
            <x-heroicon-o-book-open class="h-4 w-4" />
            <span>3. Tahfizh</span>
            <span class="rounded-full bg-amber-500 px-2 py-0.5 text-[9px] font-bold uppercase text-white">Menyusul</span>
        </button>
    </div>

    {{-- Dokumen laporan (tab 1-3) --}}
    <div>
    <div class="wali-dokumen space-y-6 rounded-3xl border border-gray-200 bg-white p-6 shadow-xl md:p-10">
        {{-- Kop --}}
        <div class="wali-kop">
            <div class="flex items-center gap-4">
                <img src="{{ asset('images/logo-pondok.jpeg') }}" alt="Logo Pondok Tahfizh Plus Abu Dzar"
                    class="h-16 w-auto object-contain">
                <div>
                    <h1 class="wali-kop-judul">Pondok Tahfizh Plus Abu Dzar</h1>
                    <h2 class="wali-kop-subjudul">Bidang Kesiswaan &amp; Kesantrian</h2>
                    <p class="mt-0.5 text-xs text-gray-500">
                        Ds. Mekar Sari, Kec. Rumpin, Kab. Bogor, Jawa Barat, 16350 &bull; Website: abudzar.ponpes.id
                    </p>
                </div>
            </div>

            <div class="hidden text-right text-xs text-gray-500 sm:block">
                <span class="mb-1 inline-block rounded-full border border-olive-200 bg-olive-50 px-3 py-1 text-center font-extrabold leading-tight text-olive-700">
                    Laporan Resmi<br>Wali Santri
                </span>
                <p>No. Dokumen: <strong class="font-mono text-gray-700">{{ $nomorDokumen }}</strong></p>
            </div>
        </div>

        {{-- Identitas --}}
        <div class="wali-identitas text-xs">
            <div>
                <span class="wali-identitas-label">Nama Lengkap Santri</span>
                <h4 class="wali-identitas-nilai text-olive-400">{{ $santri->nama_lengkap }}</h4>
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

        {{-- Judul + periode --}}
        <div class="flex flex-col items-start justify-between gap-4 rounded-2xl border border-gray-200 bg-gray-50 p-4 text-xs md:flex-row md:items-center">
            <div>
                <span class="block font-bold uppercase tracking-wider text-gray-400">Judul Laporan</span>
                <h3 class="text-base font-extrabold uppercase text-navy-900">Laporan Pekanan Wali Santri</h3>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-2 font-medium">
                <span class="block text-gray-500">Periode Laporan:</span>
                <strong class="text-xs font-bold text-navy-900">{{ $tanggalMulai }} &mdash; {{ $tanggalSelesai }}</strong>
            </div>
        </div>

        {{-- 1. KBM --}}
        <div @if ($tabAktif !== 'kbm') class="hidden print:block" @endif class="space-y-4">
            <h4 class="wali-section-title justify-between border-b border-gray-200 pb-2">
                <span class="flex items-center gap-2">
                    <x-heroicon-m-book-open class="h-4 w-4 text-olive-500" />
                    <span>1. Kegiatan Belajar Mengajar (KBM)</span>
                </span>
                <span class="text-xs font-bold text-gray-500">{{ count($kbmLaporans) }} Mata Pelajaran</span>
            </h4>

            @if (count($kbmLaporans) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full overflow-hidden rounded-xl border border-gray-200 border-collapse text-left text-xs">
                        <thead>
                            <tr class="bg-navy-900 text-[10px] font-extrabold uppercase tracking-wider text-white">
                                <th class="w-8 px-3 py-2.5 text-center">No</th>
                                <th class="w-40 px-3 py-2.5">Mata Pelajaran</th>
                                <th class="w-36 px-3 py-2.5">Guru Pengampu</th>
                                <th class="px-4 py-2.5">Materi &amp; Indikator Pembelajaran</th>
                                <th class="w-28 px-3 py-2.5 text-center">Nilai Santri</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($kbmLaporans as $idx => $lp)
                                @php
                                    $nilai = $lp->nilaiSantris->first();
                                    $angka = $nilai?->nilai;
                                @endphp
                                <tr class="font-medium hover:bg-gray-50">
                                    <td class="px-3 py-3 text-center font-bold text-gray-500">{{ $idx + 1 }}</td>
                                    <td class="px-3 py-3 font-bold text-navy-900">
                                        {{ $lp->mata_pelajaran }}
                                        <span class="block text-[10px] font-semibold text-gray-400">
                                            {{ $lp->bulan ? \Illuminate\Support\Str::ucfirst($lp->bulan) : '' }}
                                            {{ $lp->pekan ? 'Pekan '.$lp->pekan : '' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-[11px] text-gray-700">{{ $lp->nama_guru ?? '-' }}</td>
                                    <td class="px-4 py-3 leading-relaxed text-gray-800">
                                        {{ $lp->indikator ?: $lp->materi_pembelajaran ?: '-' }}
                                    </td>
                                    <td class="px-3 py-3 text-center font-bold">
                                        @if (! is_null($angka))
                                            <span @class([
                                                'wali-badge',
                                                'border-emerald-300 bg-emerald-100 text-emerald-800' => $angka >= 85,
                                                'border-blue-300 bg-blue-100 text-blue-800' => $angka >= 75 && $angka < 85,
                                                'border-amber-300 bg-amber-100 text-amber-800' => $angka < 75,
                                            ])>
                                                {{ floor($angka) == $angka ? (int) $angka : number_format($angka, 1) }}
                                            </span>
                                        @else
                                            <span class="text-[11px] font-semibold text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-6 text-center text-xs font-semibold text-gray-500">
                    <x-heroicon-o-inbox-arrow-down class="mb-2 block h-6 w-6 text-gray-400" />
                    Belum ada laporan pekanan mata pelajaran yang diinputkan guru untuk periode ini.
                </div>
            @endif
        </div>

        {{-- 2. Kesiswaan --}}
        <div @if ($tabAktif !== 'kesiswaan') class="hidden print:block" @endif class="space-y-6">
            <h4 class="wali-section-title border-b border-gray-200 pb-2">
                <x-heroicon-m-chart-pie class="h-4 w-4 text-olive-500" />
                <span>2.1 Rekapitulasi Kehadiran &amp; Kesehatan</span>
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

            {{-- Daftar kehadiran harian --}}
            @if (count($absensis) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full overflow-hidden rounded-xl border border-gray-200 border-collapse text-left text-xs">
                        <thead>
                            <tr class="bg-gray-100 text-[11px] font-extrabold uppercase text-gray-800">
                                <th class="px-3 py-2.5">Tanggal</th>
                                <th class="px-3 py-2.5 text-center">Status</th>
                                <th class="px-3 py-2.5">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($absensis as $a)
                                @php $status = $a->statusAbsensi(); @endphp
                                <tr class="font-medium hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-3 py-2 font-bold">
                                        {{ $a->tanggal?->translatedFormat('d M Y') }}
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        @if ($status)
                                            <span @class([
                                                'rounded-full px-2 py-0.5 text-[10px] font-bold',
                                                'bg-emerald-100 text-emerald-800' => $status->value === 'Hadir',
                                                'bg-blue-100 text-blue-800' => $status->value === 'Izin',
                                                'bg-amber-100 text-amber-800' => $status->value === 'Sakit',
                                                'bg-rose-100 text-rose-800' => $status->value === 'Alfa',
                                            ])>{{ $status->label() }}</span>
                                        @else
                                            <span class="text-[10px] text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-700">{{ $a->keterangan ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Sakit --}}
            @if (count($sakits) > 0)
                <div class="space-y-3">
                    <h5 class="flex items-center gap-2 text-xs font-bold text-gray-800">
                        <x-heroicon-m-heart class="h-4 w-4 text-rose-500" />
                        <span>Catatan Kesehatan &amp; Penanganan ({{ count($sakits) }} Kejadian)</span>
                    </h5>
                    <div class="overflow-x-auto">
                        <table class="w-full overflow-hidden rounded-xl border border-gray-200 border-collapse text-left text-xs">
                            <thead>
                                <tr class="bg-gray-100 text-[11px] font-extrabold uppercase text-gray-800">
                                    <th class="px-3 py-2.5">Tanggal</th>
                                    <th class="px-3 py-2.5">Keluhan / Gejala</th>
                                    <th class="px-3 py-2.5">Penanganan</th>
                                    <th class="px-3 py-2.5 text-center">Status Perawatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($sakits as $sk)
                                    <tr class="font-medium hover:bg-gray-50">
                                        <td class="whitespace-nowrap px-3 py-2 font-bold">
                                            {{ $sk->tanggal_sakit?->translatedFormat('d M Y') ?? '-' }}
                                        </td>
                                        <td class="px-3 py-2 text-gray-900">{{ $sk->keluhan ?? '-' }}</td>
                                        <td class="px-3 py-2 text-gray-700">{{ $sk->penanganan ?? 'Diberikan Istirahat & Obat UKS' }}</td>
                                        <td class="px-3 py-2 text-center font-bold">
                                            <span @class([
                                                'rounded-full px-2 py-0.5 text-[10px]',
                                                'bg-amber-100 text-amber-800' => $sk->status_perawatan === 'Dirawat di Pondok',
                                                'bg-purple-100 text-purple-800' => $sk->status_perawatan !== 'Dirawat di Pondok',
                                            ])>{{ $sk->status_perawatan ?? '-' }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Izin --}}
            @if (count($izins) > 0)
                <div class="space-y-3">
                    <h5 class="flex items-center gap-2 text-xs font-bold text-gray-800">
                        <x-heroicon-m-arrow-right-on-rectangle class="h-4 w-4 text-sky-500" />
                        <span>Catatan Izin Keluar ({{ count($izins) }} Kejadian)</span>
                    </h5>
                    <div class="overflow-x-auto">
                        <table class="w-full overflow-hidden rounded-xl border border-gray-200 border-collapse text-left text-xs">
                            <thead>
                                <tr class="bg-gray-100 text-[11px] font-extrabold uppercase text-gray-800">
                                    <th class="px-3 py-2.5">Periode Izin</th>
                                    <th class="px-3 py-2.5">Keperluan / Alasan</th>
                                    <th class="px-3 py-2.5 text-center">Kepentingan</th>
                                    <th class="px-3 py-2.5 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($izins as $iz)
                                    <tr class="font-medium hover:bg-gray-50">
                                        <td class="whitespace-nowrap px-3 py-2 font-bold">
                                            {{ $iz->tanggal_mulai?->translatedFormat('d M Y') }} &rarr;
                                            {{ $iz->tanggal_akhir?->translatedFormat('d M Y') }}
                                        </td>
                                        <td class="px-3 py-2 text-gray-900">{{ $iz->alasan ?? '-' }}</td>
                                        <td class="px-3 py-2 text-center font-bold">
                                            <span @class([
                                                'rounded-full px-2 py-0.5 text-[10px]',
                                                'bg-rose-100 text-rose-800' => $iz->status_kepentingan === 'Prioritas',
                                                'bg-gray-100 text-gray-600' => $iz->status_kepentingan !== 'Prioritas',
                                            ])>{{ $iz->status_kepentingan ?? '-' }}</span>
                                        </td>
                                        <td class="px-3 py-2 text-center font-bold">
                                            <span @class([
                                                'rounded-full px-2 py-0.5 text-[10px]',
                                                'bg-emerald-100 text-emerald-800' => $iz->status === 'Kembali',
                                                'bg-sky-100 text-sky-800' => $iz->status !== 'Kembali',
                                            ])>{{ $iz->status === 'Kembali' ? 'Sudah Kembali' : 'Sedang Izin' }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Pelanggaran --}}
            <div class="space-y-3">
                <h4 class="wali-section-title justify-between border-b border-gray-200 pb-2">
                    <span class="flex items-center gap-2">
                        <x-heroicon-m-exclamation-triangle class="h-4 w-4 text-amber-500" />
                        <span>2.2 Catatan Pelanggaran Kedisiplinan</span>
                    </span>
                    <span class="text-xs font-bold text-gray-500">Total: {{ count($pelanggaran) }} Pelanggaran</span>
                </h4>

                @if (count($pelanggaran) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full overflow-hidden rounded-xl border border-gray-200 border-collapse text-left text-xs">
                            <thead>
                                <tr class="bg-gray-100 text-[11px] font-extrabold uppercase text-gray-800">
                                    <th class="w-10 px-3 py-2.5 text-center">No</th>
                                    <th class="px-3 py-2.5">Tanggal</th>
                                    <th class="px-3 py-2.5">Jenis Pelanggaran</th>
                                    <th class="px-3 py-2.5 text-center">Tingkat</th>
                                    <th class="px-3 py-2.5">Sanksi / Tindakan</th>
                                    <th class="px-3 py-2.5 text-center">Status Sanksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($pelanggaran as $idx => $p)
                                    <tr class="font-medium hover:bg-gray-50">
                                        <td class="px-3 py-2 text-center font-bold">{{ $idx + 1 }}</td>
                                        <td class="whitespace-nowrap px-3 py-2 font-bold">
                                            {{ $p->tanggal_pelanggaran?->translatedFormat('d M Y') ?? '-' }}
                                        </td>
                                        <td class="px-3 py-2 font-bold text-gray-900">
                                            {{ $p->kategori?->nama ?? '-' }}
                                            @if ($p->catatan)
                                                <span class="block text-[11px] font-normal italic text-gray-500">&ldquo;{{ $p->catatan }}&rdquo;</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <span @class([
                                                'rounded px-2 py-0.5 text-[10px] font-extrabold uppercase',
                                                'bg-rose-100 text-rose-700' => $p->kategori?->tingkat === 'Berat',
                                                'bg-amber-100 text-amber-700' => $p->kategori?->tingkat === 'Sedang',
                                                'bg-emerald-100 text-emerald-700' => $p->kategori?->tingkat === 'Ringan' || $p->kategori?->tingkat === null,
                                            ])>{{ $p->kategori?->tingkat ?? 'Ringan' }}</span>
                                        </td>
                                        <td class="px-3 py-2 font-bold text-gray-700">{{ $p->sanksi_diberikan ?? 'Sanksi Disiplin' }}</td>
                                        <td class="px-3 py-2 text-center">
                                            <span @class([
                                                'rounded px-2 py-0.5 text-[10px] font-bold',
                                                'bg-emerald-100 text-emerald-800' => $p->status_sanksi === 'Selesai',
                                                'bg-amber-100 text-amber-800' => $p->status_sanksi !== 'Selesai',
                                            ])>{{ $p->status_sanksi ?? '-' }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-semibold text-emerald-800">
                        <x-heroicon-m-check-circle class="h-5 w-5 text-emerald-600" />
                        <span>Alhamdulillah, santri bersangkutan <strong>bebas dari pelanggaran kedisiplinan</strong> pada periode laporan ini.</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- 3. Tahfizh --}}
        <div @if ($tabAktif !== 'tahfizh') class="hidden print:block" @endif class="space-y-4">
            <h4 class="wali-section-title justify-between border-b border-gray-200 pb-2">
                <span class="flex items-center gap-2">
                    <x-heroicon-o-book-open class="h-4 w-4 text-amber-500" />
                    <span>3. Rekapitulasi Capaian Tahfizh Al-Qur&rsquo;an</span>
                </span>
                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-bold text-amber-800">
                    Menyusul / Dalam Pengembangan
                </span>
            </h4>

            @if (count($tesHafalans) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full overflow-hidden rounded-xl border border-gray-200 border-collapse text-left text-xs">
                        <thead>
                            <tr class="bg-navy-900 text-[10px] font-extrabold uppercase text-white">
                                <th class="w-10 px-3 py-2.5 text-center">No</th>
                                <th class="px-4 py-2.5">Juz Al-Qur&rsquo;an</th>
                                <th class="px-4 py-2.5 text-center">Nilai / Predikat</th>
                                <th class="px-4 py-2.5 text-center">Status Kelulusan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($tesHafalans as $idx => $th)
                                <tr class="font-medium hover:bg-gray-50">
                                    <td class="px-3 py-2.5 text-center font-bold text-gray-500">{{ $idx + 1 }}</td>
                                    <td class="px-4 py-2.5 font-bold text-navy-900">Juz {{ $th->juz ?? '-' }}</td>
                                    <td class="px-4 py-2.5 text-center font-bold text-gray-800">{{ $th->nilai ?? $th->nnilai_angka ?? '-' }}</td>
                                    <td class="px-4 py-2.5 text-center font-bold">
                                        <span @class([
                                            'rounded-full px-2 py-0.5 text-[10px]',
                                            'bg-emerald-100 text-emerald-800' => $th->is_lulus,
                                            'bg-amber-100 text-amber-800' => ! $th->is_lulus,
                                        ])>{{ $th->is_lulus ? 'Lulus / Mumtaz' : 'Prosedur Ujian' }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="space-y-3 rounded-3xl border-2 border-dashed border-amber-200 bg-amber-50/70 p-8 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 shadow-sm">
                        <x-heroicon-o-book-open class="h-7 w-7" />
                    </div>
                    <h4 class="text-sm font-extrabold text-gray-900">Modul Laporan Tahfizh (Menyusul)</h4>
                    <p class="mx-auto max-w-md text-xs leading-relaxed text-gray-600">
                        Fitur rekapitulasi hafalan harian &amp; ujian juz Al-Qur&rsquo;an secara
                        terintegrasi sedang disiapkan dan akan segera tampil pada bagian ini.
                    </p>
                </div>
            @endif
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

        {{-- Tanda tangan --}}
        <div class="flex flex-col items-center gap-6 border-t border-gray-200 pt-8 text-xs text-gray-800 sm:flex-row sm:items-start sm:justify-between">
            <div class="w-full text-center sm:w-64">
                <p class="font-bold">Orang Tua / Wali Santri,</p>
                <div class="h-16"></div>
                <p class="whitespace-nowrap font-bold text-navy-900">( ................................................. )</p>
            </div>

            <div class="w-full text-center sm:w-64">
                <p class="text-gray-600">{{ $tanggalCetak }}</p>
                <p class="mt-0.5 font-bold">Wali Kelas</p>
                <div class="h-16"></div>
                <p class="whitespace-nowrap font-bold text-navy-900">( {{ $waliKelas['nama'] ?? '........................................' }} )</p>
                @if (! empty($waliKelas['nip']) && $waliKelas['nip'] !== '-')
                    <p class="mt-0.5 text-[10px] text-gray-400">NIP / ID: {{ $waliKelas['nip'] }}</p>
                @endif
            </div>
        </div>
    </div>
    </div>

    {{-- 4. Rapor Bayangan (STS). Dokumen A4 terpisah, tidak memakai kerangka
         laporan pekanan di atas karena format resminya berbeda. --}}
    <div @if ($tabAktif !== 'rapor') class="hidden print:hidden" @endif>
    <div class="wali-rapor-sheet">

        {{-- Kop --}}
        <div class="wali-rapor-kop">
            <img src="{{ $rapor['tingkat'] === 'SMA' ? asset('images/Kop SMA.png') : asset('images/Kop SMP.png') }}"
                alt="Kop Surat Pondok Tahfizh Plus Abu Dzar">
        </div>

        {{-- Judul --}}
        <div class="wali-rapor-judul">
            <h2>Laporan Hasil Evaluasi Formatif dan Sumatif Tengah Semester (STS)</h2>
            <p>Tahun Pelajaran {{ $rapor['tahun'] ?? '-' }}</p>
        </div>

        {{-- Identitas --}}
        <div class="wali-rapor-identitas mb-3">
            <table class="wali-rapor-identitas">
                <tbody>
                    <tr>
                        <td style="width: 15%;">Nama Siswa</td>
                        <td style="width: 2%;">:</td>
                        <td class="tebal" style="width: 38%;">{{ strtoupper($santri->nama_lengkap) }}</td>
                        <td style="width: 15%;">Kelas</td>
                        <td style="width: 2%;">:</td>
                        <td class="tebal" style="width: 28%;">{{ $santri->kelas ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>NIS / NISN</td>
                        <td>:</td>
                        <td>{{ $santri->nis ?? '-' }} / {{ $santri->nisn ?? '-' }}</td>
                        <td>Semester</td>
                        <td>:</td>
                        <td>{{ $rapor['semester'] }} ({{ $rapor['semester'] === '1' ? 'Ganjil' : 'Genap' }})</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Tabel nilai --}}
        @php
            $totalNilai = 0;
            $jumlahNilai = 0;
        @endphp
        <table class="wali-rapor-table mb-3 wali-rapor-kolom-tengah">
            <thead>
                <tr class="wali-rapor-kepala">
                    <th style="width: 5%;">No</th>
                    <th class="wali-rapor-kolom-nama" style="width: 40%;">Mata Pelajaran</th>
                    <th style="width: 10%;">KKM</th>
                    <th style="width: 12%;">Nilai Angka</th>
                    <th style="width: 20%;">Nilai Huruf</th>
                    <th style="width: 13%;">Ketuntasan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rapor['mapelSettings'] as $idx => $mapel)
                    @php
                        $nilai = $rapor['nilaiPerMapel'][$mapel->id] ?? null;
                        $kkm = $rapor['kkmPerMapel'][$mapel->mata_pelajaran] ?? 80;
                        $tuntas = $nilai !== null && $nilai >= $kkm;

                        if ($nilai !== null) {
                            $totalNilai += $nilai;
                            $jumlahNilai++;
                        }
                    @endphp
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td class="wali-rapor-kolom-nama">{{ $mapel->mata_pelajaran }}</td>
                        <td>{{ $kkm }}</td>
                        <td class="font-bold">{{ $nilai !== null ? number_format($nilai, 0) : '-' }}</td>
                        <td class="capitalize">{{ $nilai !== null ? \App\Support\Terbilang::dari($nilai) : '-' }}</td>
                        <td @class([
                            $tuntas ? 'wali-rapor-tuntas' : 'wali-rapor-belum-tuntas',
                        ])>{{ $nilai !== null ? ($tuntas ? 'Tuntas' : 'Belum Tuntas') : '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="italic">Belum ada data mata pelajaran.</td>
                    </tr>
                @endforelse

                <tr class="wali-rapor-jumlah">
                    <td colspan="3" class="wali-rapor-kolom-nama uppercase">Total Nilai</td>
                    <td>{{ number_format($totalNilai, 0) }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr class="wali-rapor-jumlah">
                    <td colspan="3" class="wali-rapor-kolom-nama uppercase">Rata-Rata</td>
                    <td>{{ number_format($jumlahNilai > 0 ? round($totalNilai / $jumlahNilai, 2) : 0, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>

        {{-- Ketidakhadiran + kepribadian --}}
        <div class="wali-rapor-grid-dua">
            <table class="wali-rapor-table">
                <thead>
                    <tr class="wali-rapor-kepala">
                        <th colspan="2" class="uppercase">Ketidakhadiran</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="width: 70%;">Sakit</td>
                        <td class="wali-rapor-kolom-tengah font-bold" style="width: 30%;">{{ $rapor['catatan']->sakit ?? 0 }} hari</td>
                    </tr>
                    <tr>
                        <td>Izin</td>
                        <td class="wali-rapor-kolom-tengah font-bold">{{ $rapor['catatan']->izin ?? 0 }} hari</td>
                    </tr>
                    <tr>
                        <td>Tanpa Keterangan</td>
                        <td class="wali-rapor-kolom-tengah font-bold">{{ $rapor['catatan']->alpha ?? 0 }} hari</td>
                    </tr>
                </tbody>
            </table>

            <table class="wali-rapor-table">
                <thead>
                    <tr class="wali-rapor-kepala">
                        <th colspan="2" class="uppercase">Kepribadian</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="width: 70%;">Perilaku / Sikap</td>
                        <td class="wali-rapor-kolom-tengah font-bold" style="width: 30%;">{{ $rapor['catatan']->perilaku ?? 'A' }}</td>
                    </tr>
                    <tr>
                        <td>Kedisiplinan / Kerajinan</td>
                        <td class="wali-rapor-kolom-tengah font-bold">{{ $rapor['catatan']->kedisiplinan ?? 'A' }}</td>
                    </tr>
                    <tr>
                        <td>Kebersihan &amp; Kerapihan</td>
                        <td class="wali-rapor-kolom-tengah font-bold">{{ $rapor['catatan']->kebersihan ?? 'A' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Catatan --}}
        <table class="wali-rapor-table mb-4">
            <thead>
                <tr class="wali-rapor-kepala">
                    <th class="wali-rapor-kolom-nama uppercase">Catatan Kepribadian Siswa</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="italic text-slate-700">
                        &ldquo;{{ $rapor['catatan']->catatan_kepribadian ?? 'Tingkatkan terus kedisiplinan dan keaktifan dalam kegiatan pembelajaran di pondok pesantren.' }}&rdquo;
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- Tanda tangan --}}
        <div class="mt-6">
            <div class="wali-rapor-ttd-tanggal">{{ $tanggalCetakRapor }}</div>

            <div class="wali-rapor-ttd">
                <div class="wali-rapor-ttd-kolom">
                    <div class="wali-rapor-ttd-label">Orang Tua / Wali Santri</div>
                    <div class="wali-rapor-ttd-ruang"></div>
                    <div class="wali-rapor-ttd-nama">( ........................................ )</div>
                </div>

                <div class="wali-rapor-ttd-kolom">
                    <div class="wali-rapor-ttd-label">Wali Kelas</div>
                    <div class="wali-rapor-ttd-ruang">
                        @if (! empty($rapor['ttdWalas']))
                            <img src="{{ asset($rapor['ttdWalas']) }}" class="wali-rapor-ttd-gambar" alt="TTD Wali Kelas">
                        @endif
                    </div>
                    <div>
                        <div class="wali-rapor-ttd-nama">{{ $rapor['waliKelasNama'] ?? '........................................' }}</div>
                        <div class="wali-rapor-ttd-nip">NIP: {{ $rapor['waliKelasNip'] ?? '-' }}</div>
                    </div>
                </div>

                <div class="wali-rapor-ttd-kolom">
                    <div class="wali-rapor-ttd-label">Mengetahui,<br>Kepala Sekolah {{ $rapor['tingkat'] }}</div>
                    <div class="wali-rapor-ttd-ruang wali-rapor-ttd-ruang--kepsek">
                        @if (! empty($rapor['kepsekTtd']))
                            <img src="{{ asset($rapor['kepsekTtd']) }}" class="wali-rapor-ttd-gambar" alt="TTD Kepala Sekolah">
                        @endif
                    </div>
                    <div>
                        <div class="wali-rapor-ttd-nama">{{ $rapor['kepsekNama'] }}</div>
                        <div class="wali-rapor-ttd-nip">NIY: {{ $rapor['kepsekNiy'] ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</x-filament-panels::page>