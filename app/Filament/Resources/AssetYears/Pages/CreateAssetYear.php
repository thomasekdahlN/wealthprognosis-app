<?php

namespace App\Filament\Resources\AssetYears\Pages;

use App\Filament\Resources\AssetYears\AssetYearResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssetYear extends CreateRecord
{
    protected static string $resource = AssetYearResource::class;

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Determine asset & configuration from request context
        $assetId = (int) ($data['asset_id'] ?? request()->get('asset') ?? 0);
        $configurationId = (int) ($data['asset_configuration_id'] ?? request()->get('configuration') ?? request()->get('owner') ?? 0);

        if ($assetId > 0) {
            $data['asset_id'] = $assetId;
        }
        if ($configurationId > 0) {
            $data['asset_configuration_id'] = $configurationId;
        }

        // Auto-increment year: previous highest for the same asset + 1
        if (! isset($data['year']) || empty($data['year'])) {
            $maxYear = \App\Models\AssetYear::query()
                ->where('asset_id', $assetId)
                ->max('year');
            $data['year'] = $maxYear ? ((int) $maxYear + 1) : (int) date('Y');
        }

        // Inherit default change rates from AssetType if not provided
        if ($assetId) {
            $asset = \App\Models\Asset::with('assetType')->find($assetId);
            if ($asset && $asset->assetType) {
                $data['income_changerate'] = $data['income_changerate'] ?? $asset->assetType->income_changerate;
                $data['expence_changerate'] = $data['expence_changerate'] ?? $asset->assetType->expence_changerate;
                $data['asset_changerate'] = $data['asset_changerate'] ?? $asset->assetType->asset_changerate;
            }
        }

        return $data;
    }
}
