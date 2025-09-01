<?php

namespace App\Filament\Resources\AssetConfigurations;

use App\Filament\Resources\AssetConfigurations\Actions\CreateAiAssistedConfigurationAction;
use App\Filament\Resources\AssetConfigurations\Actions\RunSimulationAction;
use App\Filament\Resources\AssetConfigurations\Pages;
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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $maxContentWidth = 'full';

    protected static ?string $navigationLabel = 'Configurations';

    public static function shouldRegisterNavigation(): bool
    {
        // Explicitly requested: Assets menu should lead to Asset Configurations listing
        return true;
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
}
