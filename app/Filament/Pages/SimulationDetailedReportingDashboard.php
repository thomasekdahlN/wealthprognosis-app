<?php

/*
 * Copyright (C) 2024 Thomas Ekdahl
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace App\Filament\Pages;

use App\Models\SimulationConfiguration;
use Filament\Actions\Action;
use Filament\Pages\Dashboard;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Routing\Route;

class SimulationDetailedReportingDashboard extends Dashboard
{
    protected static string $routePath = '/config/{configuration}/sim/{simulation}/detailed-reports';

    protected static bool $shouldRegisterNavigation = false;

    public ?SimulationConfiguration $simulationConfiguration = null;

    private function safeRouteParam(string $key): mixed
    {
        try {
            $value = request()->route($key);
            if ($value !== null) {
                return $value;
            }
            $route = request()->route();
            if ($route instanceof Route) {
                return $route->parameter($key);
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    public function mount(): void
    {
        $configurationId = $this->safeRouteParam('configuration');
        $simulationId = $this->safeRouteParam('simulation');

        if (! $configurationId || ! $simulationId) {
            throw new Halt;
        }

        $this->simulationConfiguration = SimulationConfiguration::with([
            'assetConfiguration',
            'simulationAssets.simulationAssetYears',
            'simulationAssets.assetType',
        ])
            ->where('user_id', auth()->id())
            ->where('id', $simulationId)
            ->whereHas('assetConfiguration', function ($query) use ($configurationId) {
                $query->where('id', $configurationId);
            })
            ->first();

        if (! $this->simulationConfiguration) {
            throw new Halt;
        }

        // Store in request for widgets
        request()->merge([
            'simulation_configuration_id' => $this->simulationConfiguration->id,
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        if (! $this->simulationConfiguration) {
            return 'Detailed Reports';
        }

        return 'Detailed Reports: '.$this->simulationConfiguration->name;
    }

    public function getHeading(): string|Htmlable
    {
        if (! $this->simulationConfiguration) {
            return 'Detailed Reports';
        }

        return $this->simulationConfiguration->name.' - Detailed Reports';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (! $this->simulationConfiguration) {
            return null;
        }

        $config = $this->simulationConfiguration->assetConfiguration;
        $assetsCount = $this->simulationConfiguration->simulationAssets()->count();
        $yearsCount = $this->simulationConfiguration->simulationAssets()
            ->withCount('simulationAssetYears')
            ->get()
            ->sum('simulation_asset_years_count');

        return "Based on {$config->name} • {$assetsCount} assets • {$yearsCount} projections";
    }

    public function getWidgets(): array
    {
        if (! $this->simulationConfiguration) {
            return [];
        }

        return [
            \App\Filament\Widgets\Simulation\SimulationAssetDrillDownTableWidget::class,
            \App\Filament\Widgets\Simulation\SimulationIncomeReportWidget::class,
            \App\Filament\Widgets\Simulation\SimulationExpenseReportWidget::class,
            \App\Filament\Widgets\Simulation\SimulationTaxReportWidget::class,
            \App\Filament\Widgets\Simulation\SimulationFinancialMetricsHeatmapWidget::class,
        ];
    }

    public function getColumns(): int
    {
        return 1;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_dashboard')
                ->label('Back to Dashboard')
                ->icon('heroicon-o-arrow-left')
                ->url(function () {
                    if (! $this->simulationConfiguration) {
                        return '#';
                    }

                    return route('filament.admin.pages.simulation-dashboard', [
                        'configuration' => $this->simulationConfiguration->asset_configuration_id,
                        'simulation' => $this->simulationConfiguration->id,
                    ]);
                })
                ->color('gray'),
        ];
    }

    public static function getRouteName(?\Filament\Panel $panel = null): string
    {
        return 'filament.admin.pages.simulation-detailed-reporting-dashboard';
    }
}
