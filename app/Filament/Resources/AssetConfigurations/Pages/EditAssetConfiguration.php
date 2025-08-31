<?php

namespace App\Filament\Resources\AssetConfigurations\Pages;

use App\Filament\Resources\AssetConfigurations\AssetConfigurationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAssetConfiguration extends EditRecord
{
    protected static string $resource = AssetConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
