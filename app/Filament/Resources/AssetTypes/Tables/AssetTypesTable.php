<?php

namespace App\Filament\Resources\AssetTypes\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AssetTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Type')
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

                TextColumn::make('category')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Investment Funds' => 'success',
                        'Securities' => 'info',
                        'Real Assets' => 'warning',
                        'Cash Equivalents' => 'gray',
                        'Alternative Investments' => 'danger',
                        'Personal Assets' => 'purple',
                        'Pension & Retirement' => 'blue',
                        'Income' => 'green',
                        'Business' => 'orange',
                        'Insurance & Protection' => 'indigo',
                        default => 'secondary',
                    }),

                TextColumn::make('taxType.name')
                    ->label('Tax Type')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->placeholder('No tax type'),

                TextColumn::make('description')
                    ->label('Description')
                    ->formatStateUsing(fn (?string $state): string => strip_tags((string) $state))
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = strip_tags((string) $column->getState());
                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    }),

                // Booleans in logical sequence
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('is_private')
                    ->label('Private')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('is_company')
                    ->label('Company')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('is_tax_optimized')
                    ->label('Tax Optimized')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('tax_shield')
                    ->label('Tax Shield')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('is_liquid')
                    ->label('Liquid')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('is_investable')
                    ->label('Investable')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('is_saving')
                    ->label('Saving')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('can_generate_income')
                    ->label('Gen. Income')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('can_generate_expenses')
                    ->label('Gen. Expenses')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('can_have_mortgage')
                    ->label('Mortgage')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('can_have_market_value')
                    ->label('Market Value')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Category')
                    ->options([
                        'Investment Funds' => 'Investment Funds',
                        'Securities' => 'Securities',
                        'Real Assets' => 'Real Assets',
                        'Cash Equivalents' => 'Cash Equivalents',
                        'Alternative Investments' => 'Alternative Investments',
                        'Personal Assets' => 'Personal Assets',
                        'Pension & Retirement' => 'Pension & Retirement',
                        'Income' => 'Income',
                        'Business' => 'Business',
                        'Insurance & Protection' => 'Insurance & Protection',
                        'Debt & Liabilities' => 'Debt & Liabilities',
                        'Special' => 'Special',
                        'Reference' => 'Reference',
                    ])
                    ->multiple()
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                TernaryFilter::make('is_private')
                    ->label('Private')
                    ->placeholder('All')
                    ->trueLabel('Private only')
                    ->falseLabel('Non-private only'),

                TernaryFilter::make('is_company')
                    ->label('Company')
                    ->placeholder('All')
                    ->trueLabel('Company only')
                    ->falseLabel('Non-company only'),

                TernaryFilter::make('is_tax_optimized')
                    ->label('Tax Optimized')
                    ->placeholder('All')
                    ->trueLabel('Tax optimized only')
                    ->falseLabel('Non-tax optimized only'),

                TernaryFilter::make('is_liquid')
                    ->label('Liquid')
                    ->placeholder('All')
                    ->trueLabel('Liquid only')
                    ->falseLabel('Illiquid only'),

                TernaryFilter::make('is_investable')
                    ->label('Investable')
                    ->placeholder('All')
                    ->trueLabel('Investable only')
                    ->falseLabel('Non-investable only'),

                TernaryFilter::make('is_saving')
                    ->label('Saving')
                    ->placeholder('All')
                    ->trueLabel('Saving only')
                    ->falseLabel('Non-saving only'),

                TernaryFilter::make('tax_shield')
                    ->label('Tax Shield')
                    ->placeholder('All')
                    ->trueLabel('Tax shield only')
                    ->falseLabel('No tax shield'),

                TernaryFilter::make('can_generate_income')
                    ->label('Gen. Income')
                    ->placeholder('All')
                    ->trueLabel('Can generate income')
                    ->falseLabel('Cannot generate income'),

                TernaryFilter::make('can_generate_expenses')
                    ->label('Gen. Expenses')
                    ->placeholder('All')
                    ->trueLabel('Can generate expenses')
                    ->falseLabel('Cannot generate expenses'),

                TernaryFilter::make('can_have_mortgage')
                    ->label('Mortgage')
                    ->placeholder('All')
                    ->trueLabel('Can have mortgage')
                    ->falseLabel('Cannot have mortgage'),

                TernaryFilter::make('can_have_market_value')
                    ->label('Market Value')
                    ->placeholder('All')
                    ->trueLabel('Can have market value')
                    ->falseLabel('Cannot have market value'),
            ])
            ->striped()
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50, 100, 150]);
    }
}
