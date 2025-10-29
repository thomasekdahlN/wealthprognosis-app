<?php

namespace App\Filament\Resources\TaxConfigurations\Schemas;

use Filament\Forms\Components\Select;
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
                TextInput::make('year')
                    ->required()
                    ->numeric()
                    ->default(fn () => (int) request()->route('year')),
                Select::make('tax_type')
                    ->label('Tax Type')
                    ->options(fn () => \App\Models\TaxType::query()->ordered()->pluck('name', 'type'))
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('description')->maxLength(255),
                Toggle::make('is_active')
                    ->required(),
                Textarea::make('configuration')
                    ->label('Configuration (JSON)')
                    ->rows(12)
                    ->rules(['json'])
                    ->formatStateUsing(function ($state): string {
                        if (is_array($state)) {
                            return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        }

                        return is_string($state) ? $state : '';
                    })
                    ->dehydrateStateUsing(function ($state): ?array {
                        if (is_array($state)) {
                            return $state;
                        }
                        if (is_string($state) && $state !== '') {
                            $decoded = json_decode($state, true);

                            return is_array($decoded) ? $decoded : null;
                        }

                        return null;
                    })
                    ->dehydrated()
                    ->columnSpanFull(),

            ]);
    }
}
