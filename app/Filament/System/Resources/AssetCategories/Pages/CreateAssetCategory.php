<?php

namespace App\Filament\System\Resources\AssetCategories\Pages;

use App\Filament\System\Resources\AssetCategories\AssetCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssetCategory extends CreateRecord
{
    protected static string $resource = AssetCategoryResource::class;
}
