<?php

namespace App\Filament\Resources\AssetConfigurations\Pages;

use App\Filament\Resources\AssetConfigurations\AssetConfigurationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssetConfiguration extends CreateRecord
{
    protected static string $resource = AssetConfigurationResource::class;

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
