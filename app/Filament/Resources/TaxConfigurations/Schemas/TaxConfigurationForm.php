<?php

namespace App\Filament\Resources\TaxConfigurations\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TaxConfigurationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('country_code')
                    ->required(),
                TextInput::make('year')
                    ->required()
                    ->numeric(),
                TextInput::make('tax_type')
                    ->label('Tax Type')
                    ->required(),
                TextInput::make('description')->maxLength(255),

                TextInput::make('income_tax_rate')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('realization_tax_rate')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('fortune_tax_rate')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('property_tax_rate')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('standard_deduction')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('fortune_tax_threshold_low')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('fortune_tax_threshold_high')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('fortune_tax_rate_low')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('fortune_tax_rate_high')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('tax_shield_rate')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->required(),
                Textarea::make('configuration_data')
                    ->columnSpanFull(),
            ]);
    }
}
