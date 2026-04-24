<?php

namespace App\Filament\System\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(1),

                            TextInput::make('email')
                                ->email()
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->columnSpan(1),

                            TextInput::make('password')
                                ->password()
                                ->revealable()
                                ->maxLength(255)
                                ->required(fn (string $operation): bool => $operation === 'create')
                                ->dehydrated(fn (?string $state): bool => filled($state))
                                ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                                ->helperText(fn (string $operation): ?string => $operation === 'edit' ? 'Leave empty to keep the current password.' : null)
                                ->columnSpan(1),

                            DateTimePicker::make('email_verified_at')
                                ->label('Email verified at')
                                ->seconds(false)
                                ->columnSpan(1),
                        ]),
                    ]),

                Section::make('Access')
                    ->schema([
                        Toggle::make('is_admin')
                            ->label('System access (admin)')
                            ->helperText('Grants access to the System portal.')
                            ->default(false),
                    ]),
            ]);
    }
}
