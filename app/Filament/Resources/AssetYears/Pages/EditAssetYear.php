<?php

namespace App\Filament\Resources\AssetYears\Pages;

use App\Filament\Resources\AssetYears\AssetYearResource;
use App\Models\Asset;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditAssetYear extends EditRecord
{
    protected static string $resource = AssetYearResource::class;

    public function getTitle(): string|Htmlable
    {
        $record = $this->getRecord();
        $asset = Asset::query()->find($record->asset_id);
        if (! $asset) {
            return 'Edit Asset Year';
        }

        $assetName = $asset->name ?? 'Asset #'.$asset->id;
        $assetType = $asset->getTypeLabel();

        return 'Edit: '.$assetName.' ('.$assetType.')';
    }

    public function getBreadcrumbs(): array
    {
        $record = $this->getRecord();
        $ownerName = optional($record->assetConfiguration)->name;
        $assetName = optional($record->asset)->name;

        $crumbs = [];
        $crumbs[__('Configurations')] = \App\Filament\Resources\AssetConfigurations\AssetConfigurationResource::getUrl('index');
        if ($record->assetConfiguration) {
            $crumbs[$record->assetConfiguration->name] = \App\Filament\Resources\AssetConfigurations\AssetConfigurationResource::getUrl('assets', ['record' => $record->asset_configuration_id]);
        }
        if ($record->asset) {
            $crumbs[$record->asset->name] = null;
        }

        return $crumbs;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
