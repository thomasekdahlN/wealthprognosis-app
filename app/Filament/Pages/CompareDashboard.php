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

use App\Models\AssetConfiguration;
use App\Models\SimulationConfiguration;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Routing\Route;

class CompareDashboard extends Dashboard
{
    protected static string $routePath = '/config/{configuration}/compare';

    protected static bool $shouldRegisterNavigation = false;

    public ?AssetConfiguration $assetConfiguration = null;

    public ?SimulationConfiguration $simulationA = null;

    public ?SimulationConfiguration $simulationB = null;

    public ?int $simulationAId = null;

    public ?int $simulationBId = null;

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

        if (! $configurationId) {
            throw new Halt('404');
        }

        $this->assetConfiguration = AssetConfiguration::where('user_id', auth()->id())
            ->find($configurationId);

        if (! $this->assetConfiguration) {
            throw new Halt('404');
        }

        // Get simulation IDs from query parameters
        $this->simulationAId = request()->query('simulationA');
        $this->simulationBId = request()->query('simulationB');

        if ($this->simulationAId) {
            $this->simulationA = SimulationConfiguration::where('user_id', auth()->id())
                ->where('asset_configuration_id', $this->assetConfiguration->id)
                ->with(['simulationAssets.simulationAssetYears', 'simulationAssets.asset'])
                ->find($this->simulationAId);
        }

        if ($this->simulationBId) {
            $this->simulationB = SimulationConfiguration::where('user_id', auth()->id())
                ->where('asset_configuration_id', $this->assetConfiguration->id)
                ->with(['simulationAssets.simulationAssetYears', 'simulationAssets.asset'])
                ->find($this->simulationBId);
        }
    }

    public function getTitle(): string|Htmlable
    {
        return 'Compare Simulations';
    }

    public function getHeading(): string|Htmlable
    {
        if ($this->simulationA && $this->simulationB) {
            return "Comparing: {$this->simulationA->name} vs {$this->simulationB->name}";
        }

        return 'Compare Simulations - Select Two Scenarios';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (! $this->simulationA || ! $this->simulationB) {
            return 'Please select two simulations to compare using the form below.';
        }

        return "Configuration: {$this->assetConfiguration->name}";
    }

    protected function getHeaderActions(): array
    {
        $backUrl = '#';
        try {
            $backUrl = route('filament.admin.pages.config-simulations', [
                'configuration' => $this->assetConfiguration->id,
            ]);
        } catch (\Throwable $e) {
            // ignore
        }

        return [
            Action::make('select_simulations')
                ->label('Select Simulations')
                ->icon('heroicon-o-adjustments-horizontal')
                ->color('primary')
                ->form([
                    Select::make('simulationA')
                        ->label('Simulation A (Baseline)')
                        ->options(function () {
                            return SimulationConfiguration::where('user_id', auth()->id())
                                ->where('asset_configuration_id', $this->assetConfiguration->id)
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->required()
                        ->default($this->simulationAId),

                    Select::make('simulationB')
                        ->label('Simulation B (Scenario)')
                        ->options(function () {
                            return SimulationConfiguration::where('user_id', auth()->id())
                                ->where('asset_configuration_id', $this->assetConfiguration->id)
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->required()
                        ->default($this->simulationBId),
                ])
                ->action(function (array $data): void {
                    $this->redirect(route('filament.admin.pages.compare-dashboard', [
                        'configuration' => $this->assetConfiguration->id,
                        'simulationA' => $data['simulationA'],
                        'simulationB' => $data['simulationB'],
                    ]));
                }),

            Action::make('back_to_simulations')
                ->label('Back to Simulations')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url($backUrl),
        ];
    }

    public function getWidgets(): array
    {
        if (! $this->simulationA || ! $this->simulationB) {
            return [];
        }

        return [
            \App\Filament\Widgets\Compare\CompareScenarioAssumptionsWidget::class,
            \App\Filament\Widgets\Compare\CompareKeyOutcomesWidget::class,
            \App\Filament\Widgets\Compare\CompareNetWorthTrajectoryWidget::class,
            \App\Filament\Widgets\Compare\CompareCashFlowTrajectoryWidget::class,
            \App\Filament\Widgets\Compare\CompareDeltaChartWidget::class,
            \App\Filament\Widgets\Compare\CompareDebtLoadWidget::class,
            \App\Filament\Widgets\Compare\CompareRiskMetricsWidget::class,
            \App\Filament\Widgets\Compare\CompareAiAnalysisWidget::class,
        ];
    }

    public function getColumns(): int
    {
        return 1;
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function getWidgetData(): array
    {
        return [
            'simulationA' => $this->simulationA,
            'simulationB' => $this->simulationB,
            'assetConfiguration' => $this->assetConfiguration,
        ];
    }

    public static function getRouteName(?\Filament\Panel $panel = null): string
    {
        return 'filament.admin.pages.compare-dashboard';
    }

    public static function canAccess(): bool
    {
        return true;
    }

    /**
     * @return array<string, class-string>
     */
    public static function getRoutes(): array
    {
        return [
            '/config/{configuration}/compare' => static::class,
        ];
    }
}
