<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Resources\Assets\AssetResource;
use Filament\Resources\Pages\CreateRecord;

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
}
