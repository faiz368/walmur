{{--
    Mobile Navigation Drawer
    Hanya tampil untuk user yang sudah login dan di layar < 1024px.
    Menggunakan Alpine x-data lokal — tidak bergantung pada $store.sidebar Filament.
--}}
@php
    $menu = [
        [
            'route' => 'filament.wali.pages.beranda',
            'label' => 'Beranda',
            'desc' => 'Ringkasan & periode laporan',
            'active' => request()->routeIs('filament.wali.pages.beranda'),
            'icon' => 'm2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
        ],
        [
            'route' => 'filament.wali.pages.laporan-pekanan',
            'label' => 'Laporan Pekanan',
            'desc' => 'KBM, kesiswaan & tahfizh',
            'active' => request()->routeIs('filament.wali.pages.laporan-pekanan'),
            'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z',
        ],
        [
            'route' => 'filament.wali.pages.rapor-bayangan',
            'label' => 'Rapor Bayangan',
            'desc' => 'Nilai STS per mata pelajaran',
            'active' => request()->routeIs('filament.wali.pages.rapor-bayangan'),
            'icon' => 'M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 3.741-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5',
        ],
    ];
@endphp

@auth
    <div
        x-data="{ open: false }"
        x-on:keydown.escape.window="open = false"
        x-effect="document.body.classList.toggle('wali-nav-locked', open)"
        class="wali-mobile-nav lg:hidden"
    >
        {{-- Tombol hamburger --}}
        <button
            type="button"
            x-show="!open"
            x-on:click="open = true"
            aria-label="Buka menu navigasi"
            class="wali-hamburger-btn"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke-width="2" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>

        {{-- Backdrop --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-on:click="open = false"
            class="wali-drawer-overlay"
            style="display:none"
        ></div>

        {{-- Drawer --}}
        <aside
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="wali-drawer"
            style="display:none"
            role="dialog"
            aria-modal="true"
            aria-label="Menu navigasi"
        >
            {{-- Header --}}
            <div class="wali-drawer-header">
                <div class="wali-drawer-brand">
                    <img
                        src="{{ asset('images/logo-walmur-emblem.png') }}"
                        alt="Logo WALMUR"
                        class="wali-drawer-brand-logo"
                    >
                    <div class="wali-drawer-brand-text">
                        <span class="wali-drawer-brand-overline">Portal</span>
                        <span class="wali-drawer-brand-title">Wali Murid</span>
                    </div>
                </div>
                <button
                    type="button"
                    x-on:click="open = false"
                    aria-label="Tutup menu"
                    class="wali-drawer-close"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="2" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Menu --}}
            <p class="wali-drawer-section">Menu Utama</p>

            <nav class="wali-drawer-nav" aria-label="Menu utama">
                @foreach ($menu as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        x-on:click="open = false"
                        @class(['wali-drawer-item', 'wali-drawer-item-active' => $item['active']])
                    >
                        <span class="wali-drawer-item-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                width="20" height="20" stroke-width="1.6" stroke="currentColor"
                                class="h-5 w-5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                            </svg>
                        </span>
                        <span class="wali-drawer-item-text">
                            <span class="wali-drawer-item-label">{{ $item['label'] }}</span>
                            <span class="wali-drawer-item-desc">{{ $item['desc'] }}</span>
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            width="16" height="16" stroke-width="2" stroke="currentColor"
                            class="wali-drawer-item-chevron" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                @endforeach
            </nav>

            {{-- Footer --}}
            <div class="wali-drawer-footer">
                <div class="wali-drawer-user">
                    <span class="wali-drawer-user-avatar">
                        {{ \Illuminate\Support\Str::of(auth()->user()?->name ?? 'W')->substr(0, 1)->upper() }}
                    </span>
                    <span class="wali-drawer-user-text">
                        <span class="wali-drawer-user-caption">Masuk sebagai</span>
                        <span class="wali-drawer-user-name">{{ auth()->user()?->name ?? 'Wali Murid' }}</span>
                    </span>
                </div>
                <form method="POST" action="{{ filament()->getLogoutUrl() }}">
                    @csrf
                    <button type="submit" class="wali-drawer-logout">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.6" stroke="currentColor" class="h-5 w-5 shrink-0" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                        </svg>
                        <span>Keluar dari Portal</span>
                    </button>
                </form>
            </div>
        </aside>
    </div>
@endauth
