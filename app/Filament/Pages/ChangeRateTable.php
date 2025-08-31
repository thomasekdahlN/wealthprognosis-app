<?php

namespace App\Filament\Pages;

use App\Models\PrognosisChangeRate as AssetChangeRate;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\Request;

class ChangeRateTable extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.change-rate-table';

    public string $scenario;

    public string $asset;

    public function mount(Request $request): void
    {
        $this->scenario = $request->get('scenario', 'realistic');
        $this->asset = $request->get('asset', 'equityfund');
    }

    public function getTitle(): string|Htmlable
    {
        $scenarioLabel = \App\Models\PrognosisType::where('code', $this->scenario)->value('label') ?? ucfirst($this->scenario);
        $assetLabel = AssetChangeRate::ASSET_TYPES[$this->asset] ?? ucfirst($this->asset);

        return "Prognosis Change Rates - {$scenarioLabel} - {$assetLabel}";
    }

    public function getHeading(): string|Htmlable
    {
        $scenarioLabel = \App\Models\PrognosisType::where('code', $this->scenario)->value('label') ?? ucfirst($this->scenario);
        $assetLabel = AssetChangeRate::ASSET_TYPES[$this->asset] ?? ucfirst($this->asset);

        return "{$assetLabel} - {$scenarioLabel} Scenario";
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Configure change rates by year. Click on values to edit inline, or use the actions to add/delete rows.';
    }

    public function getMaxWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public function getNextAvailableYear(): int
    {
        $existingYears = AssetChangeRate::query()
            ->where('scenario_type', $this->scenario)
            ->where('asset_type', $this->asset)
            ->pluck('year')
            ->toArray();

        if (empty($existingYears)) {
            return now()->year;
        }

        $maxYear = max($existingYears);
        $nextYear = $maxYear + 1;

        // Check if the next year already exists, if so find the first gap
        while (in_array($nextYear, $existingYears)) {
            $nextYear++;
        }

        return $nextYear;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AssetChangeRate::query()
                    ->where('scenario_type', $this->scenario)
                    ->where('asset_type', $this->asset)
            )
            ->columns([
                TextColumn::make('year')
                    ->label('Year')
                    ->sortable()
                    ->searchable(),

                TextInputColumn::make('change_rate')
                    ->label('Change Rate (%)')
                    ->type('number')
                    ->step(0.01)
                    ->rules(['required', 'numeric'])
                    ->sortable(),

                TextInputColumn::make('description')
                    ->label('Description')
                    ->placeholder('Optional description'),

                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),
            ])
            ->defaultSort('year', 'asc')
            ->headerActions([
                CreateAction::make()
                    ->label('Add Year')
                    ->icon('heroicon-m-plus')
                    ->action(function () {
                        // Create an empty record for inline editing
                        $nextYear = $this->getNextAvailableYear();
                        AssetChangeRate::create([
                            'scenario_type' => $this->scenario,
                            'asset_type' => $this->asset,
                            'year' => $nextYear,
                            'change_rate' => 0.00,
                            'description' => '',
                            'is_active' => true,
                        ]);

                        // Refresh the table to show the new empty row
                        $this->dispatch('$refresh');
                    }),
            ])
            ->actions([
                DeleteAction::make()
                    ->icon('heroicon-m-trash')
                    ->label('')
                    ->tooltip('Delete this year')
                    ->requiresConfirmation(),
            ])
            ->emptyStateHeading('No change rates configured')
            ->emptyStateDescription('Start by adding change rates for different years using the "Add Year" button above.')
            ->emptyStateIcon('heroicon-o-chart-bar');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_assets')
                ->label('← Back to Assets')
                ->icon('heroicon-m-arrow-left')
                ->color('gray')
                ->action(function () {
                    return redirect()->route('filament.admin.pages.prognosis-change-assets', [
                        'scenario' => $this->scenario,
                    ]);
                }),

            Action::make('back_to_scenarios')
                ->label('← All Scenarios')
                ->icon('heroicon-m-home')
                ->color('gray')
                ->action(function () {
                    return redirect()->route('filament.admin.pages.prognosis-change-rates');
                }),
        ];
    }
}
