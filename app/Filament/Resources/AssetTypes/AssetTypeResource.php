<?php

namespace App\Filament\Resources\AssetTypes;

use App\Filament\Resources\AssetTypes\Pages\CreateAssetType;
use App\Filament\Resources\AssetTypes\Pages\EditAssetType;
use App\Filament\Resources\AssetTypes\Pages\ListAssetTypes;
use App\Filament\Resources\AssetTypes\Schemas\AssetTypeForm;
use App\Filament\Resources\AssetTypes\Tables\AssetTypesTable;
use App\Models\AssetType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AssetTypeResource extends Resource
{
    protected static ?string $model = AssetType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static UnitEnum|string|null $navigationGroup = 'Setup';

    protected static ?string $navigationLabel = 'Asset Types';

    protected static ?int $navigationSort = 6;

    protected static ?string $maxContentWidth = 'full';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getRecordUrl(string $name, array $parameters = []): string
    {
        return static::getUrl('edit', $parameters);
    }

    public static function form(Schema $schema): Schema
    {
        return AssetTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetTypesTable::configure($table);
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
            'index' => ListAssetTypes::route('/'),
            'create' => CreateAssetType::route('/create'),
            'edit' => EditAssetType::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

}
