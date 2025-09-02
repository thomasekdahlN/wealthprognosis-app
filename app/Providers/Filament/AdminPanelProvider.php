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
use Illuminate\Support\Facades\Blade;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
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
            ->maxContentWidth(Width::Full)
            ->sidebarCollapsibleOnDesktop()
            ->collapsedSidebarWidth('4.5rem')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->resources([
                \App\Filament\Resources\AssetYears\AssetYearResource::class,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            // ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets') // Disabled to manually control widgets
            ->widgets([
                // ROW 1: Asset Overview (sort = 0) - 4 stats in 1 widget = 4 per row
                \App\Filament\Widgets\AssetOverviewWidget::class,

                // ROW 2: Monthly Cash Flow (sort = 1) - 4 stats in 1 widget = 4 per row
                \App\Filament\Widgets\MonthlyCashflowWidget::class,

                // ROW 3: FIRE Metrics (sort = 2) - 4 stats in 1 widget = 4 per row
                \App\Filament\Widgets\FireMetricsOverviewWidget::class,

                // Chart Widgets
                \App\Filament\Widgets\NetWorthOverTimeWidget::class,
                \App\Filament\Widgets\SavingsRateOverTimeWidget::class,
                \App\Filament\Widgets\FireProgressAndCrossover::class,

                // Asset Allocation Charts (3 different groupings)
                \App\Filament\Widgets\AssetAllocationByType::class,
                \App\Filament\Widgets\AssetAllocationByTaxType::class,
                \App\Filament\Widgets\AssetAllocationByCategory::class,

                \App\Filament\Widgets\YearlyCashflowWidget::class,
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
            ]);
    }

    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_END,
            fn (): string => Blade::render('<div class="px-2"><livewire:asset-configuration-picker /></div>')
        );
    }

    protected function getBrandName(): string
    {
        $appName = config('app.name', 'Laravel');
        $activeAssetConfiguration = \App\Services\AssetConfigurationSessionService::getActiveAssetConfiguration();

        if ($activeAssetConfiguration) {
            return $appName . ' - ' . $activeAssetConfiguration->name;
        }

        return $appName;
    }

    protected function getBrandLogo(): ?string
    {
        $activeAssetConfiguration = \App\Services\AssetConfigurationSessionService::getActiveAssetConfiguration();

        if ($activeAssetConfiguration && $activeAssetConfiguration->icon) {
            $iconName = $activeAssetConfiguration->icon;
            $iconColor = $activeAssetConfiguration->color ?? 'inherit';

            return view('components.asset-configuration-brand-logo', [
                'iconName' => $iconName,
                'iconColor' => $iconColor
            ])->render();
        }

        return null;
    }
}
