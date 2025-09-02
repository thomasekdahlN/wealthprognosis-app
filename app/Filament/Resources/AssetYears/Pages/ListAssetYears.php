<?php

namespace App\Filament\Resources\AssetYears\Pages;

use App\Filament\Resources\AssetYears\AssetYearResource;
use App\Models\Asset;
use App\Models\AssetConfiguration;
use App\Models\AssetYear;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListAssetYears extends ListRecords
{
    protected static string $resource = AssetYearResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\AssetYears\Widgets\AssetYearAmountsChart::class,
        ];
    }

    public function getTitle(): string|Htmlable
    {
        $assetId = (int) request()->get('asset');
        $asset = Asset::query()->find($assetId);
        if (! $asset) {
            return 'Asset Years';
        }

        $assetName = $asset->name ?? 'Asset #'.$asset->id;
        $assetType = $asset->getTypeLabel();

        return $assetName.' ('.$assetType.')';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $assetId = (int) request()->get('asset');

                    if ($assetId && ! isset($data['year'])) {
                        // Find the highest existing year for this asset
                        $highestYear = AssetYear::where('asset_id', $assetId)
                            ->max('year');

                        if ($highestYear) {
                            // Use highest existing year + 1
                            $data['year'] = $highestYear + 1;
                        } else {
                            // Use current year if no records exist
                            $data['year'] = (int) date('Y');
                        }
                    }

                    return $data;
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $configurationId = request()->get('configuration');
        $assetId = request()->get('asset');

        $query = AssetYear::query();
        if ($configurationId) {
            $query->where('asset_configuration_id', $configurationId);
        }
        if ($assetId) {
            $query->where('asset_id', $assetId);
        }

        return $query;
    }

    public function getBreadcrumbs(): array
    {
        $configurationId = (int) request()->get('configuration');
        $assetId = (int) request()->get('asset');

        $crumbs = [];
        // Map is [url => label]
        $crumbs[\App\Filament\Resources\AssetConfigurations\AssetConfigurationResource::getUrl('index')] = __('Configurations');

        if ($configurationId) {
            $configuration = AssetConfiguration::find($configurationId);
            $crumbs[\App\Filament\Resources\AssetConfigurations\AssetConfigurationResource::getUrl('assets', ['record' => $configurationId])] = $configuration?->name ?? (__('Configuration').' #'.$configurationId);
        }

        if ($assetId) {
            $asset = Asset::find($assetId);
            $crumbs[] = $asset?->name ?? (__('Asset').' #'.$assetId); // final crumb as plain text
        }

        return $crumbs;
    }
}
