<?php

/*
 * Copyright (C) 2024 Thomas Ekdahl
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace App\Filament\Widgets\Compare;

use App\Models\SimulationConfiguration;
use Filament\Widgets\Widget;

class CompareScenarioAssumptionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.compare.compare-scenario-assumptions-widget';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public ?SimulationConfiguration $simulationA = null;

    public ?SimulationConfiguration $simulationB = null;

    public static function canView(): bool
    {
        return request()->routeIs('filament.admin.pages.compare-dashboard');
    }

    public function mount(?SimulationConfiguration $simulationA = null, ?SimulationConfiguration $simulationB = null): void
    {
        $this->simulationA = $simulationA;
        $this->simulationB = $simulationB;
    }

    protected function getViewData(): array
    {
        if (! $this->simulationA || ! $this->simulationB) {
            return [
                'simulationA' => null,
                'simulationB' => null,
                'summaryA' => '',
                'summaryB' => '',
                'changesB' => '',
            ];
        }

        // Generate summary for Simulation A
        $summaryA = $this->generateSummary($this->simulationA);

        // Generate summary for Simulation B
        $summaryB = $this->generateSummary($this->simulationB);

        // Generate changes description for Simulation B
        $changesB = $this->generateChanges($this->simulationA, $this->simulationB);

        return [
            'simulationA' => $this->simulationA,
            'simulationB' => $this->simulationB,
            'summaryA' => $summaryA,
            'summaryB' => $summaryB,
            'changesB' => $changesB,
        ];
    }

    protected function generateSummary(SimulationConfiguration $simulation): string
    {
        $assetCount = $simulation->simulationAssets->count();
        $prognosisType = ucfirst($simulation->prognosis ?? 'realistic');
        $group = ucfirst($simulation->group ?? 'all');

        $parts = [];
        $parts[] = "{$prognosisType} prognosis";
        $parts[] = "{$assetCount} asset(s)";
        $parts[] = "Group: {$group}";

        if ($simulation->tax_country) {
            $parts[] = "Tax: {$simulation->tax_country}";
        }

        return implode(', ', $parts);
    }

    protected function generateChanges(SimulationConfiguration $simA, SimulationConfiguration $simB): string
    {
        $changes = [];

        // Compare prognosis type
        if ($simA->prognosis !== $simB->prognosis) {
            $changes[] = "Prognosis changed from '{$simA->prognosis}' to '{$simB->prognosis}'";
        }

        // Compare group
        if ($simA->group !== $simB->group) {
            $changes[] = "Group changed from '{$simA->group}' to '{$simB->group}'";
        }

        // Compare tax country
        if ($simA->tax_country !== $simB->tax_country) {
            $changes[] = "Tax country changed from '{$simA->tax_country}' to '{$simB->tax_country}'";
        }

        // Compare asset count
        $assetCountA = $simA->simulationAssets->count();
        $assetCountB = $simB->simulationAssets->count();
        if ($assetCountA !== $assetCountB) {
            $diff = $assetCountB - $assetCountA;
            $changes[] = $diff > 0
                ? "Added {$diff} asset(s)"
                : 'Removed '.abs($diff).' asset(s)';
        }

        // Find new assets in B
        $assetNamesA = $simA->simulationAssets->pluck('asset.name')->filter()->toArray();
        $assetNamesB = $simB->simulationAssets->pluck('asset.name')->filter()->toArray();
        $newAssets = array_diff($assetNamesB, $assetNamesA);
        if (! empty($newAssets)) {
            $changes[] = 'New assets: '.implode(', ', $newAssets);
        }

        if (empty($changes)) {
            return 'No structural changes detected. Differences may be in asset parameters or market conditions.';
        }

        return implode('. ', $changes).'.';
    }
}
