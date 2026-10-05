<?php

namespace App\Providers\Filament;

use App\Filament\Wali\Pages\Auth\Login;
use App\Filament\Wali\Pages\Dashboard;
use Filament\Enums\UserMenuPosition;
use Filament\Navigation\MenuItem;
use Filament\View\PanelsRenderHook;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
            // Portal ini hanya punya 2 halaman, tidak ada resource/resource
            // group, jadi pencarian global tidak perlu ditampilkan.
            ->globalSearch(false)
            ->topNavigation()
            ->userMenu(false)
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): string => Blade::render('
                    <form method="POST" action="{{ filament()->getLogoutUrl() }}" class="flex items-center">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-gray-700 shadow-xs transition hover:border-red-200 hover:bg-red-50 hover:text-red-600 focus:outline-none">
                            <x-heroicon-m-arrow-left-end-on-rectangle class="h-4 w-4 text-gray-500" />
                            <span>Keluar</span>
                        </button>
                    </form>
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