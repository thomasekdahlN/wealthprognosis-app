<?php

namespace App\Filament\Resources\SimulationConfigurations\Schemas;

use App\Models\SimulationConfiguration;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\File;

class SimulationConfigurationForm
{
    /**
     * Get available tax countries from the config/tax folder structure
     */
    protected static function getAvailableTaxCountries(): array
    {
        $taxPath = config_path('tax');
        $countries = [];

        if (File::exists($taxPath)) {
            $directories = File::directories($taxPath);

            foreach ($directories as $directory) {
                $countryCode = basename($directory);

                // Map country codes to readable names
                $countryName = match ($countryCode) {
                    'no' => 'Norway',
                    'se' => 'Sweden',
                    'ch' => 'Switzerland',
                    'dk' => 'Denmark',
                    'us' => 'United States',
                    'en' => 'United Kingdom',
                    default => strtoupper($countryCode)
                };

                $countries[$countryCode] = $countryName;
            }
        }

        return $countries;
    }

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

            TextInput::make('expected_death_age')
                ->numeric()
                ->label('Expected Death Age')
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

            \App\Filament\Components\IconPicker::make('icon')
                ->label('Icon')
                ->nullable(),

            FileUpload::make('image')
                ->label('Image')
                ->image()
                ->nullable()
                ->validationAttribute('image'),

            Select::make('color')
                ->label('Color')
                ->options([
                    '#3b82f6' => '<span style="display: inline-flex; align-items: center; gap: 0.5rem;"><span style="display: inline-block; width: 1rem; height: 1rem; border-radius: 0.25rem; background-color: #3b82f6; border: 1px solid #e5e7eb;"></span>Blue</span>',
                    '#10b981' => '<span style="display: inline-flex; align-items: center; gap: 0.5rem;"><span style="display: inline-block; width: 1rem; height: 1rem; border-radius: 0.25rem; background-color: #10b981; border: 1px solid #e5e7eb;"></span>Green</span>',
                    '#f59e0b' => '<span style="display: inline-flex; align-items: center; gap: 0.5rem;"><span style="display: inline-block; width: 1rem; height: 1rem; border-radius: 0.25rem; background-color: #f59e0b; border: 1px solid #e5e7eb;"></span>Amber</span>',
                    '#ef4444' => '<span style="display: inline-flex; align-items: center; gap: 0.5rem;"><span style="display: inline-block; width: 1rem; height: 1rem; border-radius: 0.25rem; background-color: #ef4444; border: 1px solid #e5e7eb;"></span>Red</span>',
                    '#8b5cf6' => '<span style="display: inline-flex; align-items: center; gap: 0.5rem;"><span style="display: inline-block; width: 1rem; height: 1rem; border-radius: 0.25rem; background-color: #8b5cf6; border: 1px solid #e5e7eb;"></span>Purple</span>',
                    '#6b7280' => '<span style="display: inline-flex; align-items: center; gap: 0.5rem;"><span style="display: inline-block; width: 1rem; height: 1rem; border-radius: 0.25rem; background-color: #6b7280; border: 1px solid #e5e7eb;"></span>Gray</span>',
                ])
                ->allowHtml()
                ->default('#3b82f6')
                ->nullable()
                ->native(false)
                ->suffixIcon('heroicon-o-swatch'),

            TagsInput::make('tags')
                ->label('Tags')
                ->nullable()
                ->helperText('Add tags to categorize this simulation')
                ->separator(',')
                ->splitKeys(['Tab', 'Enter', ','])
                ->placeholder('Type and press Enter or comma to add tags'),

            Select::make('risk_tolerance')
                ->label('Risk Tolerance')
                ->options(SimulationConfiguration::RISK_TOLERANCE_LEVELS)
                ->default('moderate')
                ->required()
                ->helperText('Select your financial risk tolerance level for investment decisions'),

            Radio::make('tax_country')
                ->label('Tax Country')
                ->options(static::getAvailableTaxCountries())
                ->descriptions([
                    'no' => 'Norwegian tax system with wealth tax and progressive income tax',
                    'se' => 'Swedish tax system with capital gains tax and municipal tax',
                    'ch' => 'Swiss tax system with cantonal variations and wealth tax',
                ])
                ->default('no')
                ->required()
                ->inline(false)
                ->columnSpanFull(),

            Select::make('prognosis_type')
                ->label('Prognosis Type')
                ->options(\App\Models\PrognosisType::options())
                ->default('realistic')
                ->required()
                ->helperText('Select the economic scenario for growth rate projections'),

            Select::make('group')
                ->label('Asset Group Filter')
                ->options([
                    'private' => 'Private Assets Only',
                    'company' => 'Company Assets Only',
                    'both' => 'Both Private & Company',
                ])
                ->default('private')
                ->required()
                ->helperText('Choose which asset groups to include in the simulation'),
        ]);
    }
}
