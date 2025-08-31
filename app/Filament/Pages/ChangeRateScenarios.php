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
                    ->badge()
                    ->color('primary')
                    ->alignCenter(),

                TextColumn::make('total_records')
                    ->label('Total Records')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                TextColumn::make('updated_by')
                    ->label('Last updated by')
                    ->formatStateUsing(fn ($state, $record) => optional(\App\Models\PrognosisType::where('code', $record->type)->first()?->updatedBy)->name ?? '—')
                    ->badge()
                    ->color('gray'),

            ])
            ->recordUrl(function ($record) {
                return route('filament.admin.pages.prognosis-change-assets', [
                    'scenario' => $record->type,
                ]);
            })
            ->emptyStateHeading('No scenarios found')
            ->emptyStateDescription('Run the database seeders to populate change rate configurations.')
            ->emptyStateIcon('heroicon-o-chart-bar');
    }

    protected function getTableQuery(): Builder
    {
        return AssetChangeRate::query()
            ->selectRaw('
                ROW_NUMBER() OVER (ORDER BY scenario_type) as id,
                scenario_type as type,
                scenario_type as description,
                COUNT(DISTINCT asset_type) as asset_count,
                COUNT(*) as total_records
            ')
            ->groupBy('scenario_type')
            ->orderBy('scenario_type');
    }

    public function getTableRecordKey($record): string
    {
        return $record->id ?? $record->type ?? 'unknown';
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
