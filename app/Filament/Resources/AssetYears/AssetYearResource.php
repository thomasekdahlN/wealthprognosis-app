<?php

namespace App\Filament\Resources\AssetYears;

use App\Filament\Resources\AssetYears\Schemas\AssetYearForm;
use App\Filament\Resources\AssetYears\Tables\AssetYearsTable;
use App\Models\AssetYear;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AssetYearResource extends Resource
{
    protected static ?string $model = AssetYear::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $recordTitleAttribute = 'year';

    protected static ?string $maxContentWidth = 'full';

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Do not alter navigation
    }

    public static function form(Schema $schema): Schema
    {
        return AssetYearForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetYearsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssetYears::route('/'),
            'create' => Pages\CreateAssetYear::route('/create'),
            'edit' => Pages\EditAssetYear::route('/{record}/edit'),
        ];
    }

    public static function getRecordUrl(string $name, array $parameters = []): string
    {
        return static::getUrl('edit', $parameters);
    }
}
