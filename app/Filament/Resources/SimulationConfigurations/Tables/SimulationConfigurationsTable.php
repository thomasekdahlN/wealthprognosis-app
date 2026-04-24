<?php

namespace App\Filament\Resources\SimulationConfigurations\Tables;

use App\Services\SimulationExportService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SimulationConfigurationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
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
                        ->wrap()
                        ->grow(false)
                        ->extraAttributes(['class' => 'text-left']),

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
                        ->label('Visibility')
                        ->boolean()
                        ->trueIcon('heroicon-o-globe-alt')
                        ->falseIcon('heroicon-o-lock-closed')
                        ->trueColor('success')
                        ->falseColor('warning')
                        ->tooltip(fn ($record): string => $record->public ? 'Public' : 'Private')
                        ->sortable()
                        ->toggleable(),

                    TextColumn::make('updated_at')
                        ->label('Last Updated')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(),
                ]),

                Panel::make([
                    TextColumn::make('description')
                        ->html()
                        ->color('gray')
                        ->columnSpanFull()
                        ->placeholder('No description')
                        ->wrap(),
                ]),
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

                ActionGroup::make([
                    Action::make('export_json_full')
                        ->label('Export JSON (Full)')
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->action(function (\App\Models\SimulationConfiguration $record): StreamedResponse {
                            $json = SimulationExportService::toJson($record);
                            $filename = now()->format('Y-m-d').'_'.\Illuminate\Support\Str::slug($record->name).'_full.json';

                            return response()->streamDownload(function () use ($json) {
                                echo $json;
                            }, $filename, [
                                'Content-Type' => 'application/json',
                            ]);
                        }),

                    Action::make('export_json_compact')
                        ->label('Export JSON (Compact)')
                        ->icon('heroicon-o-document-text')
                        ->color('success')
                        ->action(function (\App\Models\SimulationConfiguration $record): StreamedResponse {
                            $json = SimulationExportService::toCompactJson($record);
                            $filename = now()->format('Y-m-d').'_'.\Illuminate\Support\Str::slug($record->name).'_compact.json';

                            return response()->streamDownload(function () use ($json) {
                                echo $json;
                            }, $filename, [
                                'Content-Type' => 'application/json',
                            ]);
                        }),

                    Action::make('export_csv_full')
                        ->label('Export CSV (Full)')
                        ->icon('heroicon-o-table-cells')
                        ->color('warning')
                        ->action(function (\App\Models\SimulationConfiguration $record): StreamedResponse {
                            $csv = SimulationExportService::toCsvFull($record);
                            $filename = now()->format('Y-m-d').'_'.\Illuminate\Support\Str::slug($record->name).'_full.csv';

                            return response()->streamDownload(function () use ($csv) {
                                echo $csv;
                            }, $filename, [
                                'Content-Type' => 'text/csv',
                            ]);
                        }),

                    Action::make('export_csv_compact')
                        ->label('Export CSV (Compact)')
                        ->icon('heroicon-o-table-cells')
                        ->color('primary')
                        ->action(function (\App\Models\SimulationConfiguration $record): StreamedResponse {
                            $csv = SimulationExportService::toCsvCompact($record);
                            $filename = now()->format('Y-m-d').'_'.\Illuminate\Support\Str::slug($record->name).'_compact.csv';

                            return response()->streamDownload(function () use ($csv) {
                                echo $csv;
                            }, $filename, [
                                'Content-Type' => 'text/csv',
                            ]);
                        }),

                    Action::make('export_excel')
                        ->label('Export Excel')
                        ->icon('heroicon-o-document-chart-bar')
                        ->color('danger')
                        ->action(function (\App\Models\SimulationConfiguration $record): \Symfony\Component\HttpFoundation\BinaryFileResponse {
                            $filePath = SimulationExportService::toExcel($record);
                            $filename = now()->format('Y-m-d').'_'.\Illuminate\Support\Str::slug($record->name).'.xlsx';

                            return response()->download($filePath, $filename)->deleteFileAfterSend(true);
                        }),
                ])
                    ->label('Export')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                BulkAction::make('compare')
                    ->label('Compare Simulations')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('primary')
                    ->requiresConfirmation(false)
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        if ($records->count() !== 2) {
                            Notification::make()
                                ->title('Please select exactly 2 simulations')
                                ->warning()
                                ->send();

                            return;
                        }

                        /** @var \App\Models\SimulationConfiguration $simulation1 */
                        $simulation1 = $records->first();
                        /** @var \App\Models\SimulationConfiguration $simulation2 */
                        $simulation2 = $records->last();

                        // Redirect to CompareDashboard with query parameters
                        return redirect()->route('filament.admin.pages.compare-dashboard', [
                            'configuration' => $simulation1->asset_configuration_id,
                            'simulationA' => $simulation1->id,
                            'simulationB' => $simulation2->id,
                        ]);
                    }),
            ])
            ->defaultSort('name')
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50, 100, 150])
            ->recordUrl(fn (\App\Models\SimulationConfiguration $record) => \App\Filament\Resources\SimulationConfigurations\SimulationConfigurationResource::getUrl('view', ['record' => $record->getKey()])
            );
    }
}
