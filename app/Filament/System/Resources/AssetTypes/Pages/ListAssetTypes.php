<?php

namespace App\Filament\System\Resources\AssetTypes\Pages;

use App\Filament\System\Resources\AssetTypes\AssetTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssetTypes extends ListRecords
{
    protected static string $resource = AssetTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
