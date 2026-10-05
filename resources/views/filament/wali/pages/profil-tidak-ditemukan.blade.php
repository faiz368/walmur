<x-filament-panels::page>
    <div class="mx-auto max-w-2xl py-8 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 shadow-sm">
            <x-heroicon-o-exclamation-triangle class="h-8 w-8" />
        </div>

        <h1 class="wali-page-title mt-5">Profil Belum Terhubung</h1>

        <p class="mt-2 text-sm text-gray-600">
            Akun wali murid <span class="font-bold text-navy-900">{{ $emailAkun }}</span>
            sudah berhasil masuk, tetapi datanya belum tertaut ke data santri di
            Sistem Informasi Pondok.
        </p>

        <div class="mt-6 space-y-3 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-left text-sm text-amber-900">
            <p class="font-bold">Laporan belum dapat ditampilkan karena:</p>
            <ul class="list-inside list-disc space-y-1 text-xs leading-relaxed">
                <li>Akun email anak ini belum ditautkan ke data santri di Learner Management System.</li>
                <li>Akun ini baru dibuat, atau datanya belum diperbarui oleh Bagian Kesiswaan.</li>
                <li>Akun email anak terdaftar dengan ejaan yang berbeda dari data admisi.</li>
            </ul>
        </div>

        <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-5 text-left text-xs shadow-sm">
            <h2 class="font-bold text-gray-900">Yang perlu dilakukan</h2>
            <p class="mt-1 leading-relaxed text-gray-600">
                Hubungi Bagian Kesiswaan &amp; Kesantrian Pondok Tahfizh Plus Abu Dzar dan
                sampaikan email akun tersebut. Setelah data tertaut, laporan pekanan anak
                akan otomatis dapat diakses tanpa perlu melakukan setting ulang.
            </p>
            <p class="mt-3 text-gray-500">
                Atau wali murid dapat hadir langsung ke bagian kesiswaan untuk proses verifikasi data.
            </p>
        </div>

        <div class="mt-6 flex items-center justify-center gap-3">
            <x-filament::button tag="a" size="sm" color="gray" outlined icon="heroicon-m-arrow-path"
                href="{{ \App\Filament\Wali\Pages\Dashboard::getUrl() }}">
                Coba Lagi
            </x-filament::button>

            <form method="POST" action="{{ filament()->getLogoutUrl() }}">
                @csrf
                <x-filament::button type="submit" size="sm" color="danger" icon="heroicon-m-arrow-right-on-rectangle">
                    Keluar
                </x-filament::button>
            </form>
        </div>
    </div>
</x-filament-panels::page>