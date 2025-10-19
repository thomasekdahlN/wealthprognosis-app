<?php

namespace App\Filament\Resources\AssetConfigurations;

use App\Filament\Resources\AssetConfigurations\Schemas\AssetConfigurationForm;
use App\Filament\Resources\AssetConfigurations\Tables\AssetConfigurationsTable;
use App\Models\AssetConfiguration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AssetConfigurationResource extends Resource
{
    protected static ?string $model = AssetConfiguration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog8Tooth;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $maxContentWidth = 'full';

    protected static ?string $navigationLabel = 'Configurations';

    public static function shouldRegisterNavigation(): bool
    {
        // Explicitly requested: Assets menu should lead to Asset Configurations listing
        return true;
    }

    public static function getUrl(?string $name = null, array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        $name = $name ?? 'index';

        // Map to our pretty route names
        return match ($name) {
            'index' => route('filament.admin.resources.asset-configurations.index.pretty', [], $isAbsolute),
            'create' => route('filament.admin.resources.asset-configurations.create.pretty', [], $isAbsolute),
            'edit' => (function () use ($parameters, $isAbsolute) {
                $recordParam = $parameters['record'] ?? null;
                if (! $recordParam) {
                    // Defensive: if no record provided, fall back to index
                    return route('filament.admin.resources.asset-configurations.index.pretty', [], $isAbsolute);
                }

                $recordId = $recordParam instanceof \Illuminate\Database\Eloquent\Model
                    ? $recordParam->getKey()
                    : $recordParam;

                return route('filament.admin.resources.asset-configurations.edit.pretty', [
                    'record' => $recordId,
                ], $isAbsolute);
            })(),
            default => parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant, $shouldGuessMissingParameters),
        };
    }

    public static function form(Schema $schema): Schema
    {
        return AssetConfigurationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetConfigurationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssetConfigurations::route('/'),
            'assets' => Pages\ConfigurationAssets::route('/{record}/assets'),
            'create' => Pages\CreateAssetConfiguration::route('/create'),
            'edit' => Pages\EditAssetConfiguration::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
