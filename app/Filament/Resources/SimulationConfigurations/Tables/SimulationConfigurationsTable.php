<?php

namespace App\Filament\Resources\SimulationConfigurations\Tables;

use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SimulationConfigurationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('icon')
                    ->label('')
                    ->icon(fn (?string $state): string => $state && \App\Helpers\HeroiconValidator::isValid($state) ? $state : 'heroicon-o-calculator')
                    ->size('lg')
                    ->color(fn ($record): string => $record->color ?: 'gray')
                    ->width('60px')
                    ->tooltip(fn ($record): string => $record->icon ? "Icon: {$record->icon}" : 'Default calculator icon'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => strip_tags((string) $record->description))
                    ->wrap(),

                TextColumn::make('assetConfiguration.name')
                    ->label('Base Configuration')
                    ->searchable()
                    ->sortable()
                    ->placeholder('None')
                    ->description('Asset configuration this simulation is based on'),

                TextColumn::make('birth_year')
                    ->label('Birth Year')
                    ->sortable()
                    ->alignStart()
                    ->formatStateUsing(fn (?int $state): string => $state ? (string) $state : ''),

                TextColumn::make('pension_official_age')
                    ->label('Official Pension Age')
                    ->numeric(locale: \App\Helpers\AmountHelper::getNorwegianLocale())
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('pension_wish_age')
                    ->label('Desired Pension Age')
                    ->numeric(locale: \App\Helpers\AmountHelper::getNorwegianLocale())
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('risk_tolerance')
                    ->label('Risk Tolerance')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'conservative' => 'success',
                        'moderate_conservative' => 'info',
                        'moderate' => 'warning',
                        'moderate_aggressive' => 'danger',
                        'aggressive' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => \App\Models\SimulationConfiguration::RISK_TOLERANCE_LEVELS[$state] ?? $state
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tax_country')
                    ->label('Tax Country')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (string $state): string => \App\Models\SimulationConfiguration::getTaxCountries()[$state] ?? strtoupper($state)
                    )
                    ->toggleable(),

                TextColumn::make('prognosis_type')
                    ->label('Prognosis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'realistic' => 'success',
                        'positive' => 'info',
                        'negative' => 'danger',
                        'tenpercent' => 'warning',
                        'zero' => 'gray',
                        'variable' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => \App\Models\PrognosisType::query()->where('code', $state)->value('label') ?? $state
                    )
                    ->toggleable(),

                TextColumn::make('group')
                    ->label('Group')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'private' => 'success',
                        'company' => 'warning',
                        'both' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => \App\Models\SimulationConfiguration::GROUP_TYPES[$state] ?? $state
                    )
                    ->toggleable(),

                IconColumn::make('public')
                    ->label('Public')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('tags')
                    ->label('Tags')
                    ->badge()
                    ->separator(',')
                    ->color('primary')
                    ->limit(3)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (is_array($state) && count($state) > 3) {
                            return 'All tags: '.implode(', ', $state);
                        }

                        return null;
                    }),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filters([
                SelectFilter::make('risk_tolerance')
                    ->label('Risk Tolerance')
                    ->options(\App\Models\SimulationConfiguration::RISK_TOLERANCE_LEVELS)
                    ->multiple(),

                SelectFilter::make('tax_country')
                    ->label('Tax Country')
                    ->options(\App\Models\SimulationConfiguration::getTaxCountries())
                    ->multiple(),

                SelectFilter::make('prognosis_type')
                    ->label('Prognosis Type')
                    ->options(\App\Models\PrognosisType::options())
                    ->multiple(),

                SelectFilter::make('group')
                    ->label('Asset Group')
                    ->options(\App\Models\SimulationConfiguration::GROUP_TYPES)
                    ->multiple(),

                SelectFilter::make('asset_configuration_id')
                    ->label('Base Configuration')
                    ->relationship('assetConfiguration', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('public')
                    ->label('Visibility')
                    ->placeholder('All simulations')
                    ->trueLabel('Public only')
                    ->falseLabel('Private only'),
            ])
            ->toggleColumnsTriggerAction(fn ($action) => $action->modalHeading('Choose columns'))
            ->actions([
                Action::make('dashboard')
                    ->label('Dashboard')
                    ->icon('heroicon-o-chart-bar')
                    ->color('primary')
                    ->url(fn (\App\Models\SimulationConfiguration $record) => route('filament.admin.pages.simulation-dashboard', ['configuration' => $record->asset_configuration_id, 'simulation' => $record->id])
                    )
                    ->openUrlInNewTab(false),

                Action::make('simulate')
                    ->label('Simulate')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->url(fn (\App\Models\SimulationConfiguration $record) => route('filament.admin.pages.simulation-assets', ['configuration' => $record->asset_configuration_id, 'simulation' => $record->id])
                    )
                    ->openUrlInNewTab(false),
            ])
            ->defaultSort('name')
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50, 100, 150])
            ->recordUrl(fn (\App\Models\SimulationConfiguration $record) => \App\Filament\Resources\SimulationConfigurations\SimulationConfigurationResource::getUrl('view', ['record' => $record->getKey()])
            );
    }
}
