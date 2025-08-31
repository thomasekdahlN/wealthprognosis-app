<?php

namespace App\Filament\Resources\AssetCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AssetCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('icon')
                    ->label('')
                    ->icon(fn (string $state): string => $state ?: 'heroicon-o-question-mark-circle')
                    ->size('lg')
                    ->color('primary'),

                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    }),

                TextColumn::make('color')
                    ->label('Color')
                    ->badge()
                    ->color(fn (string $state): string => $state),

                TextColumn::make('asset_types_count')
                    ->label('Asset Types')
                    ->counts('assetTypes')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->alignCenter(),

                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)

            ->filters([
                SelectFilter::make('color')
                    ->label('Color Theme')
                    ->options([
                        'gray' => 'Gray',
                        'success' => 'Success',
                        'info' => 'Info',
                        'warning' => 'Warning',
                        'danger' => 'Danger',
                        'primary' => 'Primary',
                        'secondary' => 'Secondary',
                        'blue' => 'Blue',
                        'green' => 'Green',
                        'red' => 'Red',
                        'purple' => 'Purple',
                        'indigo' => 'Indigo',
                        'cyan' => 'Cyan',
                        'pink' => 'Pink',
                        'slate' => 'Slate',
                    ]),

                \Filament\Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->deferLoading()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->extremePaginationLinks()
            ->paginationPageOptions([10, 25, 50, 100, 'all'])
            ->poll('30s');
    }
}
