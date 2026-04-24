<?php

namespace App\Filament\System\Resources\Users\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->toggleable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable(),

                IconColumn::make('is_admin')
                    ->label('System access')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('email_verified_at')
                    ->label('Verified at')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_admin')
                    ->label('System access')
                    ->placeholder('All')
                    ->trueLabel('Admins only')
                    ->falseLabel('Non-admins only'),

                TernaryFilter::make('email_verified_at')
                    ->label('Email verified')
                    ->placeholder('All')
                    ->trueLabel('Verified')
                    ->falseLabel('Unverified')
                    ->nullable(),
            ], layout: FiltersLayout::AboveContent)
            ->defaultSort('name')
            ->striped()
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50, 100, 150]);
    }
}
