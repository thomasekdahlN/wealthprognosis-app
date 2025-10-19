<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName(fn () => $this->getBrandName())
            ->brandLogo(fn () => $this->getBrandLogo())
            ->brandLogoHeight('2rem')
            ->maxContentWidth(Width::Full)
            ->sidebarCollapsibleOnDesktop()
            ->collapsedSidebarWidth('4.5rem')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->assets([
                \Filament\Support\Assets\Css::make('ai-assistant', resource_path('css/filament/admin/ai-assistant.css')),
            ])
            ->resources([
                \App\Filament\Resources\AssetYears\AssetYearResource::class,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->widgets([
                // ROW 1: Asset Overview (sort = 0) - 4 stats in 1 widget = 4 per row
                \App\Filament\Widgets\AssetOverviewWidget::class,

                // ROW 2: Monthly Cash Flow (sort = 1) - 4 stats in 1 widget = 4 per row
                \App\Filament\Widgets\MonthlyCashflowWidget::class,

                // Chart Widgets (FIRE grouped together; removed projections)
                // Removed NetWorthOverTimeWidget per request
                // Removed YearlyCashflowWidget per request
                // Keep FIRE widgets grouped together
                \App\Filament\Widgets\FireProgressAndCrossover::class,
                \App\Filament\Widgets\SavingsRateOverTimeWidget::class,

                // FIRE chart + single-value widgets in one row
                \App\Filament\Widgets\FireMetricsOverview::class,
                \App\Filament\Widgets\FireCrossoverWidget::class,

                // Net Worth & Cash Flow over time (respect active asset configuration)
                \App\Filament\Widgets\NetWorthOverTime::class,
                \App\Filament\Widgets\CashFlowOverTime::class,

                // Asset Allocation Charts (3 different groupings)
                \App\Filament\Widgets\AssetAllocationByType::class,
                \App\Filament\Widgets\AssetAllocationByTaxType::class,
                \App\Filament\Widgets\AssetAllocationByCategory::class,

                // Additional charts
                \App\Filament\Widgets\ActualTaxRateOverTime::class,
                \App\Filament\Widgets\RetirementReadinessChart::class,
                \App\Filament\Widgets\ExpenseBreakdownChart::class,
            ])
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
            ->routes(function (): void {
                // Pretty URLs for Asset Configurations
                \Illuminate\Support\Facades\Route::get('config', \App\Filament\Resources\AssetConfigurations\Pages\ListAssetConfigurations::class)
                    ->name('resources.asset-configurations.index.pretty');
                \Illuminate\Support\Facades\Route::get('config/create', \App\Filament\Resources\AssetConfigurations\Pages\CreateAssetConfiguration::class)
                    ->name('resources.asset-configurations.create.pretty');
                \Illuminate\Support\Facades\Route::get('config/{record}/edit', \App\Filament\Resources\AssetConfigurations\Pages\EditAssetConfiguration::class)
                    ->name('resources.asset-configurations.edit.pretty');

                // Pretty URLs including configuration ID for Assets
                \Illuminate\Support\Facades\Route::get('config/{configuration}/assets', \App\Filament\Resources\Assets\Pages\ListAssets::class)
                    ->name('resources.assets.index.pretty');
                \Illuminate\Support\Facades\Route::get('config/{configuration}/assets/create', \App\Filament\Resources\Assets\Pages\CreateAsset::class)
                    ->name('resources.assets.create.pretty');
                \Illuminate\Support\Facades\Route::get('config/{configuration}/assets/{record}/edit', \App\Filament\Resources\Assets\Pages\EditAsset::class)
                    ->name('resources.assets.edit.pretty');

                // Override Filament auto-registered slug routes with pretty, canonical names
                // Config Assets index (clicking a configuration row)
                \Illuminate\Support\Facades\Route::get('config/{record}/assets', \App\Filament\Pages\ConfigAssets::class)
                    ->middleware([\Filament\Http\Middleware\Authenticate::class])
                    ->name('pages.config-assets.pretty');

                // Config-scoped Pages (Events & Simulations) with canonical names
                \Illuminate\Support\Facades\Route::get('config/{configuration}/events', \App\Filament\Pages\ConfigEvents::class)
                    ->middleware([\Filament\Http\Middleware\Authenticate::class])
                    ->name('pages.config-events.pretty');
                \Illuminate\Support\Facades\Route::get('config/{configuration}/simulations', \App\Filament\Pages\ConfigSimulations::class)
                    ->middleware([\Filament\Http\Middleware\Authenticate::class])
                    ->name('pages.config-simulations.pretty');

                // Simulation Pages - pretty route for Simulation Assets
                \Illuminate\Support\Facades\Route::get('config/{configuration}/sim/{simulation}/assets', \App\Filament\Pages\SimulationAssets::class)
                    ->middleware([\Filament\Http\Middleware\Authenticate::class])
                    ->name('pages.simulation-assets.pretty');

                // Pretty URL for Config Asset Years list (one asset)
                \Illuminate\Support\Facades\Route::get('config/{configuration}/assets/{asset}/years', \App\Filament\Pages\ConfigAssetYears::class)
                    ->middleware([\Filament\Http\Middleware\Authenticate::class])
                    ->name('pages.config-asset-years.pretty');

                // Pretty URL for Asset Years edit
                \Illuminate\Support\Facades\Route::get('config/{configuration}/asset-years/{record}/edit', \App\Filament\Resources\AssetYears\Pages\EditAssetYear::class)
                    ->name('resources.asset-years.edit.pretty');
            });
    }

    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_LOGO_AFTER,
            fn (): string => Blade::render('<span aria-hidden="true" class="inline-block" style="width: 2ch;"></span><div class="inline-flex items-center align-middle"><livewire:asset-configuration-picker /></div>')
        );

        // Add AI Assistant Widget to all pages (only for authenticated users with a team)
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): string => auth()->check() && auth()->user()?->current_team_id
                ? Blade::render('<livewire:ai-assistant-widget />')
                : ''
        );

        // Global download handler for pages
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): string => Blade::render('<script>document.addEventListener("livewire:init",()=>{window.addEventListener("download-file",(event)=>{const d=event?.detail||{};const url=d.url||event.url;const filename=d.filename||event.filename||"";if(!url)return;const a=document.createElement("a");a.href=url;a.download=filename;a.style.display="none";document.body.appendChild(a);a.click();document.body.removeChild(a);});});</script>')
        );

        // Wide table behavior (page-wide horizontal scroll, no inner scroll)
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): string => Blade::render('<style>.wide-table .fi-ta .overflow-x-auto{overflow-x:visible!important}.wide-table .fi-ta table{width:max-content!important;min-width:max-content!important}</style><script>document.addEventListener("livewire:init",()=>{window.addEventListener("wide-table-enable",()=>{try{document.documentElement.classList.add("wide-table");document.documentElement.style.setProperty("overflow-x","auto","important");document.body.style.setProperty("overflow-x","auto","important");const sels=[".fi-layout",".fi-main",".fi-body",".fi-content",".fi-section",".fi-simple-layout"];sels.forEach(sel=>document.querySelectorAll(sel).forEach(el=>el.style.setProperty("overflow-x","visible","important")));}catch(e){}});});</script>')
        );
    }

    protected function getBrandName(): string
    {
        $appName = config('app.name', 'Laravel');
        $activeAssetConfiguration = app(\App\Services\CurrentAssetConfiguration::class)->get();

        if ($activeAssetConfiguration) {
            return $appName.' - '.$activeAssetConfiguration->name;
        }

        return $appName;
    }

    protected function getBrandLogo(): ?string
    {
        // Filament expects an image URL here; returning HTML will break the <img src> tag.
        // Use public/logo.png per project preference, if it exists.
        $logoPath = public_path('logo.png');
        if (is_file($logoPath)) {
            return asset('logo.png');
        }

        // Fallback to Filament default if no logo is present.
        return null;
    }
}
