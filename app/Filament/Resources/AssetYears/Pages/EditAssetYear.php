<?php

namespace App\Filament\Resources\AssetYears\Pages;

use App\Filament\Resources\AssetYears\AssetYearResource;
use App\Models\Asset;
use Filament\Actions\Action;
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

    public static function getRoutes(): array
    {
        return [
            '/config/{configuration}/asset-years/{record}/edit' => static::class,
        ];
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
        $record = $this->getRecord();

        return [
            Action::make('add_event')
                ->label('Add Event')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->form([
                    \Filament\Forms\Components\Select::make('asset_id')
                        ->label('Asset')
                        ->options(function () use ($record) {
                            if ($record?->asset_configuration_id) {
                                return \App\Models\Asset::query()
                                    ->where('asset_configuration_id', $record->asset_configuration_id)
                                    ->pluck('name', 'id');
                            }

                            return [];
                        })
                        ->required()
                        ->searchable()
                        ->preload(),
                    \Filament\Forms\Components\TextInput::make('year')
                        ->label('Year')
                        ->numeric()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    // Create or fetch AssetYear for the chosen asset/year then redirect to its edit page
                    $assetId = (int) $data['asset_id'];
                    $year = (int) $data['year'];
                    $asset = \App\Models\Asset::query()->findOrFail($assetId);
                    $assetYear = \App\Models\AssetYear::query()->firstOrCreate([
                        'asset_id' => $assetId,
                        'asset_configuration_id' => $asset->asset_configuration_id,
                        'year' => $year,
                    ]);
                    $this->redirect(\App\Filament\Resources\AssetYears\AssetYearResource::getUrl('edit', ['record' => $assetYear->id]));
                })
                ->modalHeading('Add Event')
                ->modalSubmitActionLabel('Continue')
                ->modalWidth('md'),
            DeleteAction::make(),
        ];
    }
}
