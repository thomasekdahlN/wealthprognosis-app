<?php

namespace App\Filament\Resources\Assets;

use App\Filament\Resources\Assets\Pages\CreateAsset;
use App\Filament\Resources\Assets\Pages\EditAsset;
use App\Filament\Resources\Assets\Pages\ListAssets;
use App\Filament\Resources\Assets\Schemas\AssetForm;
use App\Filament\Resources\Assets\Tables\AssetsTable;
use App\Models\Asset;
use App\Services\CurrentAssetConfiguration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $maxContentWidth = 'full';

    protected static ?string $navigationLabel = 'Assets';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getRecordUrl(string $name, array $parameters = []): string
    {
        return static::getUrl('edit', $parameters);
    }

    public static function getUrl(?string $name = null, array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        $name ??= 'index';

        // Prefer pretty routes that include configuration
        // Resolve configuration ID
        $configurationId = $parameters['configuration'] ?? null;
        if (! $configurationId && isset($parameters['record']) && $parameters['record'] instanceof \App\Models\Asset) {
            $configurationId = $parameters['record']->asset_configuration_id;
        }

        if (! $configurationId) {
            $configurationId = app(\App\Services\CurrentAssetConfiguration::class)->id();
        }

        if ($configurationId && $name === 'index') {
            return route('filament.admin.resources.assets.index.pretty', [
                'configuration' => $configurationId,
            ], $isAbsolute);
        }

        if ($configurationId && $name === 'create') {
            return route('filament.admin.resources.assets.create.pretty', [
                'configuration' => $configurationId,
            ], $isAbsolute);
        }

        if ($configurationId && $name === 'edit') {
            $recordParam = $parameters['record'] ?? $parameters['asset'] ?? null;
            if (! $recordParam) {
                // Defensive: if no record provided, fall back to index to avoid URL gen errors
                return route('filament.admin.resources.assets.index.pretty', [
                    'configuration' => $configurationId,
                ], $isAbsolute);
            }

            return route('filament.admin.resources.assets.edit.pretty', [
                'configuration' => $configurationId,
                'record' => $recordParam,
            ], $isAbsolute);
        }

        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant, $shouldGuessMissingParameters);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()->with(['configuration', 'assetType']);

        // Scope by the active configuration stored in session/service only (no querystring fallback)
        $activeAssetConfigurationId = (int) (app(CurrentAssetConfiguration::class)->id() ?? 0);

        if ($activeAssetConfigurationId > 0) {
            $query->where('asset_configuration_id', $activeAssetConfigurationId);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return AssetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssets::route('/'),
            'create' => CreateAsset::route('/create'),
            'edit' => EditAsset::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::query();
        $activeAssetConfigurationId = app(\App\Services\CurrentAssetConfiguration::class)->id();
        if ($activeAssetConfigurationId) {
            $query->where('asset_configuration_id', $activeAssetConfigurationId);
        }

        return (string) $query->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }
}
