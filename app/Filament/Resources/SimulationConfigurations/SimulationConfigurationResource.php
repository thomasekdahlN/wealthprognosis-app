<?php

namespace App\Filament\Resources\SimulationConfigurations;

use App\Filament\Resources\SimulationConfigurations\Pages\CreateSimulationConfiguration;
use App\Filament\Resources\SimulationConfigurations\Pages\EditSimulationConfiguration;
use App\Filament\Resources\SimulationConfigurations\Pages\ListSimulationConfigurations;
use App\Filament\Resources\SimulationConfigurations\Pages\ViewSimulationConfiguration;
use App\Filament\Resources\SimulationConfigurations\Schemas\SimulationConfigurationForm;
use App\Filament\Resources\SimulationConfigurations\Tables\SimulationConfigurationsTable;
use App\Models\SimulationConfiguration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SimulationConfigurationResource extends Resource
{
    protected static ?string $model = SimulationConfiguration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $maxContentWidth = 'full';

    protected static ?string $navigationLabel = 'Simulations';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return SimulationConfigurationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SimulationConfigurationsTable::configure($table);
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
            'index' => ListSimulationConfigurations::route('/'),
            'create' => CreateSimulationConfiguration::route('/create'),
            'view' => ViewSimulationConfiguration::route('/{record}'),
            'edit' => EditSimulationConfiguration::route('/{record}/edit'),
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
