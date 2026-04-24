<?php

namespace App\Filament\System\Resources\AssetCategories\Pages;

use App\Filament\System\Resources\AssetCategories\AssetCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssetCategories extends ListRecords
{
    protected static string $resource = AssetCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
