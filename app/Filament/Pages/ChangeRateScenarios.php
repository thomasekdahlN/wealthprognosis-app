<?php

namespace App\Filament\Pages;

use App\Models\PrognosisChangeRate as AssetChangeRate;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ChangeRateScenarios extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static \UnitEnum|string|null $navigationGroup = 'Setup';

    protected static ?string $navigationLabel = 'Prognosis Change Rates';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return (string) \App\Models\PrognosisChangeRate::query()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'secondary';
    }

    protected string $view = 'filament.pages.change-rate-scenarios';

    public function getTitle(): string|Htmlable
    {
        return 'Prognosis Change Rates';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Select Prognosis Type';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Choose a prognosis to configure change rates';
    }

    public function getMaxWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('type')
                    ->label('Prognosis Type')
                    ->badge()
                    ->formatStateUsing(function (string $state): string {
                        $p = \App\Models\PrognosisType::where('code', $state)->first();

                        return $p?->label ?? ucfirst($state);
                    })
                    ->icon(fn (string $state) => optional(\App\Models\PrognosisType::where('code', $state)->first())->icon)
                    ->color(fn (string $state) => optional(\App\Models\PrognosisType::where('code', $state)->first())->color ?? 'gray')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->formatStateUsing(fn (string $state): string => $this->getScenarioDescription($state))
                    ->wrap()
                    ->limit(80),

                TextColumn::make('asset_count')
                    ->label('Asset Types')
                    ->numeric()
                    ->alignEnd(),

                TextColumn::make('total_records')
                    ->label('Total Records')
                    ->numeric()
                    ->alignEnd(),

                TextColumn::make('updated_by')
                    ->label('Last updated by')
                    ->formatStateUsing(fn ($state, $record) => optional(\App\Models\PrognosisType::where('code', $record->type)->first()?->updatedBy)->name ?? '—')
                    ->badge()
                    ->color('gray'),

            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->filters([])
            ->toggleColumnsTriggerAction(fn ($action) => $action->modalHeading('Choose columns'))
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50, 100, 150])
            ->recordUrl(function ($record) {
                return route('filament.admin.pages.change-rate-assets', [
                    'scenario' => $record->type,
                ]);
            })
            ->emptyStateHeading('No scenarios found')
            ->emptyStateDescription('Run the database seeders to populate change rate configurations.')
            ->emptyStateIcon('heroicon-o-chart-bar');
    }

    protected function getTableQuery(): Builder
    {
        // Base on PrognosisType so all types are listed, even with 0 change rate records
        $query = \App\Models\PrognosisType::query()
            ->select(['prognoses.id', 'prognoses.code as type', 'prognoses.code as description'])
            ->orderBy('prognoses.code');

        // Add counts via subselects
        $query->selectSub(function ($q) {
            $q->from('prognosis_change_rates as pcr')
                ->selectRaw('COUNT(DISTINCT asset_type)')
                ->whereColumn('pcr.scenario_type', 'prognoses.code');
        }, 'asset_count');

        $query->selectSub(function ($q) {
            $q->from('prognosis_change_rates as pcr2')
                ->selectRaw('COUNT(*)')
                ->whereColumn('pcr2.scenario_type', 'prognoses.code');
        }, 'total_records');

        return $query;
    }

    public function getTableRecordKey($record): string
    {
        // Ensure stable, unique Livewire keys to avoid row collisions (skip numeric id as it may be 0 for aliased selects)
        return (string) ($record->type ?? 'unknown');
    }

    public function getScenarioTypes(): array
    {
        // Get all available scenario types from the database
        $scenarioTypes = AssetChangeRate::select('scenario_type')
            ->distinct()
            ->orderBy('scenario_type')
            ->pluck('scenario_type')
            ->toArray();

        // Add descriptions and counts
        $scenarios = [];
        foreach ($scenarioTypes as $type) {
            $assetCount = AssetChangeRate::where('scenario_type', $type)
                ->distinct('asset_type')
                ->count('asset_type');

            $totalRecords = AssetChangeRate::where('scenario_type', $type)->count();

            $scenarios[] = [
                'type' => $type,
                'label' => ucfirst($type),
                'description' => $this->getScenarioDescription($type),
                'asset_count' => $assetCount,
                'total_records' => $totalRecords,
            ];
        }

        return $scenarios;
    }

    private function getScenarioDescription(string $type): string
    {
        return match ($type) {
            'realistic' => 'Conservative growth assumptions based on historical averages',
            'positive' => 'Optimistic growth scenario with higher returns',
            'negative' => 'Pessimistic scenario with lower or negative returns',
            'tenpercent' => 'Fixed 10% annual growth across all assets',
            'zero' => 'Zero growth scenario for stress testing',
            'variable' => 'Variable rates that change over time',
            'stole' => 'Custom scenario with specific assumptions',
            default => 'Custom scenario configuration',
        };
    }

    private function getScenarioColor(string $type): string
    {
        return match ($type) {
            'realistic' => 'success',
            'positive' => 'info',
            'negative' => 'danger',
            'tenpercent' => 'warning',
            default => 'gray',
        };
    }
}
