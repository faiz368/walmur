<?php

namespace App\Providers\Filament;

use App\Filament\Wali\Pages\Auth\Login;
use App\Filament\Wali\Pages\Dashboard;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class WaliPanelProvider extends PanelProvider
{
    /**
     * WarnaTaken dari halaman referensi /laporan-ortu/wali di StudentHub.
     */
    public const NAVY = '#041c39';

    public const NAVY_800 = '#06264d';

    public const OLIVE = '#6da444';

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('wali')
            ->path('wali')
            ->viteTheme('resources/css/filament/wali/theme.css')
            ->font('Plus Jakarta Sans', provider: GoogleFontProvider::class)
            ->brandName('Portal Wali Murid')
            ->brandLogo(asset('images/logo-walmur-emblem.png'))
            ->brandLogoHeight('2.75rem')
            ->favicon(asset('images/favicon-walmur.png'))
            ->colors([
                'primary' => Color::hex(self::OLIVE),
                'navy' => Color::hex(self::NAVY),
                'gray' => Color::hex(self::NAVY_800),
            ])
            ->login(Login::class)
            // Tanpa halaman profil: baris `users` milik aplikasi StudentHub dan
            // wali tidak boleh mengubahnya dari portal ini. Filament tetap
            // menampilkan nama wali sebagai header (bukan tautan) di user menu.
            ->darkMode(false)
            ->globalSearch(false)
            ->topNavigation()
            ->userMenu(false)
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): string => view('filament.wali.components.mobile-nav')->render(),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): string => Blade::render('
                    {{-- Tombol keluar: hanya desktop. Mobile pakai drawer. --}}
                    <form method="POST" action="{{ filament()->getLogoutUrl() }}" class="hidden items-center lg:flex">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-gray-700 shadow-xs transition hover:border-red-200 hover:bg-red-50 hover:text-red-600 focus:outline-none">
                            <x-heroicon-m-arrow-left-end-on-rectangle class="h-4 w-4 text-gray-500" />
                            <span>Keluar</span>
                        </button>
                    </form>
                '),
            )
            // Tag PWA: viewport-fit=cover (agar safe-area iPhone aktif), manifest,
            // ikon home screen, dan warna status bar saat di-install.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('
                    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
                    <meta name="theme-color" content="#041c39" />
                    <meta name="mobile-web-app-capable" content="yes" />
                    <meta name="apple-mobile-web-app-capable" content="yes" />
                    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
                    <meta name="apple-mobile-web-app-title" content="WALMUR" />
                    <link rel="manifest" href="{{ asset(\'manifest.webmanifest\') }}" />
                    <link rel="apple-touch-icon" href="{{ asset(\'icons/apple-touch-icon.png\') }}" />
                    {{-- Navigasi mobile: file CSS statis (bukan hasil build Vite) --}}
                    <link rel="stylesheet" href="{{ asset(\'css/wali-mobile.css\') }}?v={{ file_exists(public_path(\'css/wali-mobile.css\')) ? filemtime(public_path(\'css/wali-mobile.css\')) : 1 }}" />
                '),
            )
            // Pendaftaran service worker (PWA).
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => Blade::render('
                    <script>
                        if (\'serviceWorker\' in navigator) {
                            window.addEventListener(\'load\', function () {
                                navigator.serviceWorker.register(\'/sw.js\').catch(function () {});
                            });
                        }
                    </script>
                '),
            )
            ->discoverPages(in: app_path('Filament/Wali/Pages'), for: 'App\Filament\Wali\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Wali/Widgets'), for: 'App\Filament\Wali\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
