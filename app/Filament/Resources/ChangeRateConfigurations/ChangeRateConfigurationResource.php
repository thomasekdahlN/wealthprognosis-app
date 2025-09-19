<?php

namespace App\Filament\Resources\ChangeRateConfigurations;

use App\Filament\Resources\ChangeRateConfigurations\Pages\CreateChangeRateConfiguration;
use App\Filament\Resources\ChangeRateConfigurations\Pages\ListChangeRateConfigurations;
use App\Filament\Resources\ChangeRateConfigurations\Schemas\ChangeRateConfigurationForm;
use App\Filament\Resources\ChangeRateConfigurations\Tables\ChangeRateConfigurationsTable;
use App\Models\PrognosisChangeRate as ChangeRateConfiguration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ChangeRateConfigurationResource extends Resource
{
    protected static ?string $model = ChangeRateConfiguration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $maxContentWidth = 'full';

    public static function form(Schema $schema): Schema
    {
        return ChangeRateConfigurationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChangeRateConfigurationsTable::configure($table);
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
            'index' => ListChangeRateConfigurations::route('/'),
            'create' => CreateChangeRateConfiguration::route('/create'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'secondary';
    }
}
