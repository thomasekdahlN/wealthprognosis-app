<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Resources\Assets\AssetResource;
use App\Models\AssetYear;
use Filament\Resources\Pages\CreateRecord;

/**
 * @property \App\Models\Asset $record
 */
class CreateAsset extends CreateRecord
{
    protected static string $resource = AssetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $activeId = (int) (app(\App\Services\CurrentAssetConfiguration::class)->id() ?? 0);
        if ($activeId > 0) {
            $data['asset_configuration_id'] = $activeId;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $asset = $this->record; // The newly created asset
        $year = (int) date('Y');

        AssetYear::firstOrCreate(
            [
                'asset_id' => $asset->id,
                'year' => $year,
            ],
            [
                'asset_configuration_id' => $asset->asset_configuration_id,
            ]
        );
    }
}
