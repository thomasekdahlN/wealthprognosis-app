<?php

namespace App\Filament\Resources\ChangeRateConfigurations\Schemas;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ChangeRateConfigurationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Prognosis Change Rate Configuration')
                    ->description('Configure change rates per prognosis and asset type')
                    ->schema([
                        Select::make('scenario_type')
                            ->label('Prognosis Type')
                            ->options(fn () => \App\Models\PrognosisType::query()->active()->orderBy('code')->pluck('label', 'code')->all())
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('asset_type')
                            ->label('Asset Type')
                            ->options(\App\Models\PrognosisChangeRate::assetTypeOptions())
                            ->required()
                            ->searchable()
                            ->preload(),

                        TextInput::make('year')
                            ->label('Year')
                            ->required()
                            ->numeric()
                            ->minValue(2020)
                            ->maxValue(2100)
                            ->default(now()->year),

                        TextInput::make('change_rate')
                            ->label('Change Rate (%)')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->suffix('%')
                            ->helperText('Enter the annual change rate as a percentage'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Whether this rate is currently active'),

                        Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Optional description for this change rate configuration')
                            ->columnSpanFull()
                            ->rows(3),
                    ]),
            ]);
    }
}
