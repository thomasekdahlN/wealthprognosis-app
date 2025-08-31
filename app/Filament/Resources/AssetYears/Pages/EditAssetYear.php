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
        $ownerName = optional($record->assetOwner)->name;
        $assetName = optional($record->asset)->name;

        $crumbs = [];
        $crumbs[__('Owners')] = \App\Filament\Resources\AssetOwners\AssetOwnerResource::getUrl('index');
        if ($record->assetOwner) {
            $crumbs[$record->assetOwner->name] = \App\Filament\Resources\AssetOwners\AssetOwnerResource::getUrl('assets', ['record' => $record->asset_owner_id]);
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
