<?php

namespace App\Filament\Resources\AiInstructions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AiInstructionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(50)
                    ->wrap(),

                TextColumn::make('model')
                    ->label('AI Model')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('max_tokens')
                    ->label('Max Tokens')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('temperature')
                    ->label('Temperature')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->formatStateUsing(fn ($state) => number_format($state, 1)),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('Order')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('user.name')
                    ->label('Owner')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All instructions')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order')
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50, 100, 150]);
    }
}
