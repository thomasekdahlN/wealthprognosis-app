<?php

namespace App\Filament\Resources\SimulationConfigurations\Schemas;

use App\Models\AssetConfiguration;
use App\Models\SimulationConfiguration;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;


class SimulationConfigurationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('asset_configuration_id')
                ->label('Asset Configuration')
                ->relationship('assetConfiguration', 'name')
                ->searchable()
                ->preload()
                ->nullable()
                ->helperText('Optional: Base this simulation on an existing asset configuration'),

            TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(255),

            RichEditor::make('description')
                ->label('Description')
                ->columnSpanFull()
                ->extraAttributes(['style' => 'min-height: calc(1.5rem * 8);']),

            TextInput::make('birth_year')
                ->numeric()
                ->label('Birth Year')
                ->minValue(1925)
                ->maxValue(2125)
                ->helperText('Year of birth for age-based calculations'),

            TextInput::make('prognose_age')
                ->numeric()
                ->label('Prognose Age')
                ->minValue(0)
                ->maxValue(125)
                ->helperText('Age until which to run the simulation'),

            TextInput::make('pension_official_age')
                ->numeric()
                ->label('Official Pension Age')
                ->minValue(0)
                ->maxValue(125)
                ->default(67)
                ->helperText('Official retirement age in your country'),

            TextInput::make('pension_wish_age')
                ->numeric()
                ->label('Desired Pension Age')
                ->minValue(0)
                ->maxValue(125)
                ->helperText('Age when you want to retire'),

            TextInput::make('death_age')
                ->numeric()
                ->label('Life Expectancy')
                ->minValue(0)
                ->maxValue(125)
                ->default(85)
                ->helperText('Expected life expectancy for planning'),

            TextInput::make('export_start_age')
                ->numeric()
                ->label('Export Start Age')
                ->minValue(0)
                ->maxValue(125)
                ->helperText('Age from which to include data in exports'),

            Toggle::make('public')
                ->label('Public Simulation')
                ->default(false)
                ->helperText('Make this simulation visible to other users'),

            TextInput::make('icon')
                ->label('Icon')
                ->placeholder('heroicon-o-chart-bar')
                ->helperText('Enter a Heroicon name (e.g., heroicon-o-chart-bar)')
                ->nullable(),

            FileUpload::make('image')
                ->label('Image')
                ->image()
                ->nullable(),

            ColorPicker::make('color')
                ->label('Color')
                ->nullable(),

            TagsInput::make('tags')
                ->label('Tags')
                ->nullable()
                ->helperText('Add tags to categorize this simulation'),

            Select::make('risk_tolerance')
                ->label('Risk Tolerance')
                ->options(SimulationConfiguration::RISK_TOLERANCE_LEVELS)
                ->default('moderate')
                ->required()
                ->helperText('Select your financial risk tolerance level for investment decisions'),
        ]);
    }
}
