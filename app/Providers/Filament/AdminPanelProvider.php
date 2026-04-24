<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Register;
use App\Filament\Pages\ConfigAssets;
use App\Filament\Pages\ConfigAssetYears;
use App\Filament\Pages\ConfigEvents;
use App\Filament\Pages\ConfigSimulations;
use App\Filament\Pages\SimulationAssets;
use App\Filament\Pages\SimulationAssetYears;
use App\Filament\Resources\AssetConfigurations\Pages\CreateAssetConfiguration;
use App\Filament\Resources\AssetConfigurations\Pages\EditAssetConfiguration;
use App\Filament\Resources\AssetConfigurations\Pages\ListAssetConfigurations;
use App\Filament\Resources\Assets\Pages\CreateAsset;
use App\Filament\Resources\Assets\Pages\EditAsset;
use App\Filament\Resources\Assets\Pages\ListAssets;
use App\Filament\Resources\AssetYears\AssetYearResource;
use App\Filament\Resources\AssetYears\Pages\EditAssetYear;
use App\Filament\Widgets\Configuration\ConfigurationAssetAllocationByCategoryWidget;
use App\Filament\Widgets\Configuration\ConfigurationAssetAllocationByTaxTypeWidget;
use App\Filament\Widgets\Configuration\ConfigurationAssetAllocationByTypeWidget;
use App\Filament\Widgets\Configuration\ConfigurationAssetOverviewWidget;
use App\Filament\Widgets\Configuration\ConfigurationCashFlowOverTimeWidget;
use App\Filament\Widgets\Configuration\ConfigurationExpenseBreakdownWidget;
use App\Filament\Widgets\Configuration\ConfigurationFireCrossoverWidget;
use App\Filament\Widgets\Configuration\ConfigurationFireMetricsOverviewWidget;
use App\Filament\Widgets\Configuration\ConfigurationMonthlyCashflowWidget;
use App\Filament\Widgets\Configuration\ConfigurationNetWorthOverTimeWidget;
use App\Filament\Widgets\Configuration\ConfigurationProjectionNoticeWidget;
use App\Filament\Widgets\Configuration\ConfigurationRetirementReadinessWidget;
use App\Filament\Widgets\Configuration\ConfigurationSavingsRateOverTimeWidget;
use App\Services\CurrentAssetConfiguration;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentView;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use WatheqAlshowaiter\FilamentStickyTableHeader\StickyTableHeaderPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->registration(Register::class)
            ->profile(isSimple: false)
            ->multiFactorAuthentication([
                AppAuthentication::make()
                    ->recoverable(),
            ])
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
                Css::make('ai-assistant', resource_path('css/filament/admin/ai-assistant.css')),
                Css::make('simulation-asset-years', resource_path('css/filament/admin/simulation-asset-years.css')),
            ])
            ->plugins([
                StickyTableHeaderPlugin::make(),
            ])
            ->resources([
                AssetYearResource::class,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->widgets([
                // TOP: Projection vs. Simulation notice (sort = -10)
                ConfigurationProjectionNoticeWidget::class,

                // ROW 1: Asset Overview (sort = 0) - 4 stats in 1 widget = 4 per row
                ConfigurationAssetOverviewWidget::class,

                // ROW 2: Monthly Cash Flow (sort = 1) - 4 stats in 1 widget = 4 per row
                ConfigurationMonthlyCashflowWidget::class,

                // FIRE context widgets
                ConfigurationFireCrossoverWidget::class,
                ConfigurationExpenseBreakdownWidget::class,
                ConfigurationRetirementReadinessWidget::class,

                // Net Worth & Cash Flow over time (respect active asset configuration)
                ConfigurationNetWorthOverTimeWidget::class,
                ConfigurationCashFlowOverTimeWidget::class,

                // Asset Allocation Charts (3 different groupings)
                ConfigurationAssetAllocationByTypeWidget::class,
                ConfigurationAssetAllocationByTaxTypeWidget::class,
                ConfigurationAssetAllocationByCategoryWidget::class,

                // FIRE progress charts (after allocation charts)
                ConfigurationFireMetricsOverviewWidget::class,
                ConfigurationSavingsRateOverTimeWidget::class,
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
            ->userMenuItems([
                Action::make('system-portal')
                    ->label('System portal')
                    ->icon(Heroicon::Cog6Tooth)
                    ->url(fn (): string => url('/system'))
                    ->visible(fn (): bool => (bool) (auth()->user()?->is_admin ?? false)),
            ])
            ->routes(function (): void {
                // Pretty URLs for Asset Configurations
                Route::get('config', ListAssetConfigurations::class)
                    ->name('resources.asset-configurations.index.pretty');
                Route::get('config/create', CreateAssetConfiguration::class)
                    ->name('resources.asset-configurations.create.pretty');
                Route::get('config/{record}/edit', EditAssetConfiguration::class)
                    ->name('resources.asset-configurations.edit.pretty');

                // Pretty URLs including configuration ID for Assets
                Route::get('config/{configuration}/assets', ListAssets::class)
                    ->name('resources.assets.index.pretty');
                Route::get('config/{configuration}/assets/create', CreateAsset::class)
                    ->name('resources.assets.create.pretty');
                Route::get('config/{configuration}/assets/{record}/edit', EditAsset::class)
                    ->name('resources.assets.edit.pretty');

                // Override Filament auto-registered slug routes with pretty, canonical names
                // Config Assets index (clicking a configuration row)
                Route::get('config/{record}/assets', ConfigAssets::class)
                    ->middleware([Authenticate::class])
                    ->name('pages.config-assets.pretty');

                // Config-scoped Pages (Events & Simulations) with canonical names
                Route::get('config/{configuration}/events', ConfigEvents::class)
                    ->middleware([Authenticate::class])
                    ->name('pages.config-events.pretty');
                Route::get('config/{configuration}/simulations', ConfigSimulations::class)
                    ->middleware([Authenticate::class])
                    ->name('pages.config-simulations.pretty');

                // Simulation Pages - pretty route for Simulation Assets
                Route::get('config/{configuration}/sim/{simulation}/assets', SimulationAssets::class)
                    ->middleware([Authenticate::class])
                    ->name('pages.simulation-assets.pretty');

                // Pretty URL for Simulation Asset Years list (one simulation asset)
                Route::get('config/{configuration}/sim/{simulation}/assets/{asset}/years', SimulationAssetYears::class)
                    ->middleware([Authenticate::class])
                    ->name('pages.simulation-asset-years.pretty');

                // Pretty URL for Config Asset Years list (one asset)
                Route::get('config/{configuration}/assets/{asset}/years', ConfigAssetYears::class)
                    ->middleware([Authenticate::class])
                    ->name('pages.config-asset-years.pretty');

                // Pretty URL for Asset Years edit
                Route::get('config/{configuration}/asset-years/{record}/edit', EditAssetYear::class)
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
        $activeAssetConfiguration = app(CurrentAssetConfiguration::class)->get();

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
