<?php

namespace App\Providers\Filament;

use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use WatheqAlshowaiter\FilamentStickyTableHeader\StickyTableHeaderPlugin;

class SystemPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('system')
            ->path('system')
            ->login()
            ->brandName(fn (): string => config('app.name', 'Laravel').' · System')
            ->brandLogo(fn (): ?string => is_file(public_path('logo.png')) ? asset('logo.png') : null)
            ->brandLogoHeight('2rem')
            ->maxContentWidth(Width::Full)
            ->sidebarCollapsibleOnDesktop()
            ->collapsedSidebarWidth('4.5rem')
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->plugins([
                StickyTableHeaderPlugin::make(),
            ])
            ->discoverResources(in: app_path('Filament/System/Resources'), for: 'App\\Filament\\System\\Resources')
            ->discoverPages(in: app_path('Filament/System/Pages'), for: 'App\\Filament\\System\\Pages')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->userMenuItems([
                Action::make('user-portal')
                    ->label('User portal')
                    ->icon(Heroicon::UserCircle)
                    ->url(fn (): string => url('/admin')),
            ]);
    }
}
