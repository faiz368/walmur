<x-filament-panels::page.simple>
    <div class="wali-login-shell">
        {{-- Ikon utama, identik dengan halaman referensi --}}
        <div class="wali-login-heading text-center">
            <div class="flex justify-center mb-5">
                <img
                    src="{{ asset('images/logo-walmur-emblem.png') }}"
                    alt="Logo WALMUR"
                    class="h-28 w-auto object-contain transition-transform duration-300 hover:scale-105 sm:h-40"
                />
            </div>
            <h1 class="wali-login-title">Portal Wali Murid</h1>
            <p class="wali-login-subtitle">
                Lihat laporan pekanan anak Anda tanpa perlu akun &amp; tanpa password.
            </p>
        </div>

        {{-- Kartu form --}}
        <div class="wali-login-card">
            <form wire:submit="authenticate">
                <label for="wali-login-email" class="wali-login-label">
                    Email Murid
                </label>

                <div class="wali-login-input-wrap">
                    <span class="wali-login-input-icon">
                        <x-heroicon-o-user class="h-5 w-5" />
                    </span>
                    <input
                        id="wali-login-email"
                        type="email"
                        name="email"
                        autocomplete="email"
                        required
                        autofocus
                        wire:model="data.email"
                        placeholder="Contoh: nama@mail.anak.sch.id"
                        @class([
                            'wali-login-input',
                            'wali-login-input--error' => $errors->has('data.email'),
                        ])
                    />
                </div>

                @error('data.email')
                    <p class="wali-login-error">
                        <x-heroicon-m-exclamation-circle class="mr-1 inline h-4 w-4" />
                        {{ $message }}
                    </p>
                @enderror

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="authenticate"
                    class="wali-login-button"
                >
                    <span wire:loading.remove wire:target="authenticate" class="inline-flex items-center justify-center gap-2">
                        <x-heroicon-m-magnifying-glass class="h-5 w-5 shrink-0" />
                        <span>Lihat Laporan</span>
                    </span>
                    <span wire:loading wire:target="authenticate" class="inline-flex items-center justify-center gap-2">
                        <svg class="h-5 w-5 animate-spin shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        <span>Memeriksa data...</span>
                    </span>
                </button>
            </form>

            <div class="wali-login-assurance">
                <p>
                    <x-heroicon-m-lock-open class="mr-1 inline h-3.5 w-3.5" />
                    Tanpa password &mdash; cukup email anak.
                </p>
                <p>
                    <x-heroicon-m-user-group class="mr-1 inline h-3.5 w-3.5" />
                    Hanya laporan milik anak yang dapat diakses.
                </p>
                <p>
                    <x-heroicon-m-shield-exclamation class="mr-1 inline h-3.5 w-3.5" />
                    Data bersifat rahasia &amp; hanya untuk wali murid.
                </p>
            </div>
        </div>
    </div>
</x-filament-panels::page.simple>