<?php

namespace App\Filament\Resources\TaxConfigurations;

use App\Filament\Resources\TaxConfigurations\Pages\CreateTaxConfiguration;
use App\Filament\Resources\TaxConfigurations\Pages\EditTaxConfiguration;
use App\Filament\Resources\TaxConfigurations\Pages\ListTaxConfigurations;
use App\Filament\Resources\TaxConfigurations\Schemas\TaxConfigurationForm;
use App\Filament\Resources\TaxConfigurations\Tables\TaxConfigurationsTable;
use App\Models\TaxConfiguration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TaxConfigurationResource extends Resource
{
    protected static ?string $model = TaxConfiguration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static \UnitEnum|string|null $navigationGroup = 'Setup';

    protected static ?string $navigationLabel = 'Tax Configurations';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $maxContentWidth = 'full';

    public static function getRecordUrl(string $name, array $parameters = []): string
    {
        return static::getUrl('edit', $parameters);
    }

    public static function getDefaultPage(): string
    {
        return 'index';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true; // Keep visible in Setup group; root opens Choose Country
    }

    public static function getNavigationUrl(): string
    {
        return static::getUrl('index');
    }

    public static function getUrl(?string $name = null, array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        $name ??= 'index';

        if (in_array($name, ['index', 'create', 'edit'], true)) {
            if (! array_key_exists('country', $parameters) || ! array_key_exists('year', $parameters)) {
                return parent::getUrl('index', [], $isAbsolute, $panel, $tenant, $shouldGuessMissingParameters);
            }
        }

        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant, $shouldGuessMissingParameters);
    }

    public static function form(Schema $schema): Schema
    {
        return TaxConfigurationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxConfigurationsTable::configure($table);
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
            'index' => Pages\ChooseCountry::route('/'),
            'choose-year' => Pages\ChooseYear::route('/{country}'),
            'list' => ListTaxConfigurations::route('/{country}/{year}'),
            'create' => CreateTaxConfiguration::route('/{country}/{year}/create'),
            'edit' => EditTaxConfiguration::route('/{country}/{year}/{record}/edit'),
        ];
    }
}
