<?php

namespace App\Filament\Resources\TaxTypes;

use App\Filament\Resources\TaxTypes\Pages\CreateTaxType;
use App\Filament\Resources\TaxTypes\Pages\EditTaxType;
use App\Filament\Resources\TaxTypes\Pages\ListTaxTypes;
use App\Filament\Resources\TaxTypes\Schemas\TaxTypeForm;
use App\Filament\Resources\TaxTypes\Tables\TaxTypesTable;
use App\Models\TaxType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TaxTypeResource extends Resource
{
    protected static ?string $model = TaxType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static \UnitEnum|string|null $navigationGroup = 'Taxes';

    protected static ?string $navigationLabel = 'Tax Types';

    protected static ?int $navigationSort = 3;

    protected static ?string $maxContentWidth = 'full';

    public static function getRecordUrl(string $name, array $parameters = []): string
    {
        return static::getUrl('edit', $parameters);
    }

    public static function form(Schema $schema): Schema
    {
        return TaxTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxTypesTable::configure($table);
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
            'index' => ListTaxTypes::route('/'),
            'create' => CreateTaxType::route('/create'),
            'edit' => EditTaxType::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
