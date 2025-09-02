<?php

namespace App\Filament\Pages;

use App\Models\SimulationAsset;
use App\Models\SimulationAssetYear;
use App\Models\SimulationConfiguration;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Actions\Action;
use Filament\Panel;

class SimulationAssetYears extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.simulation-asset-years';

    protected static bool $shouldRegisterNavigation = false;

    public ?SimulationConfiguration $simulationConfiguration = null;
    public ?SimulationAsset $simulationAsset = null;

    public function mount(): void
    {
        $simulationConfigurationId = request()->query('simulation_configuration');
        $assetId = request()->query('asset');

        if ($simulationConfigurationId) {
            $this->simulationConfiguration = SimulationConfiguration::find($simulationConfigurationId);
        }

        if ($assetId) {
            $this->simulationAsset = SimulationAsset::find($assetId);
        }

        // Ensure user has access
        if ($this->simulationConfiguration && $this->simulationConfiguration->user_id !== auth()->id()) {
            abort(403);
        }
    }

    public function getTitle(): string|Htmlable
    {
        if ($this->simulationAsset && $this->simulationConfiguration) {
            return "Simulation Asset Years: {$this->simulationAsset->name}";
        }

        return 'Simulation Asset Years';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if ($this->simulationConfiguration) {
            return "Simulation: {$this->simulationConfiguration->name}";
        }

        return null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->striped()
            ->columns([
                // Year column - bold and prominent
                TextColumn::make('year')
                    ->label('Year')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->size('sm')
                    ->color('primary')
                    ->width('70px'),

                // Age column
                TextColumn::make('age')
                    ->label('Age')
                    ->size('sm')
                    ->width('60px')
                    ->getStateUsing(function ($record) {
                        $birthYear = $this->simulationConfiguration->birth_year ?? 1990;
                        return $record->year - $birthYear;
                    }),

                // Income Amount - green background like Excel (#90EE90)
                TextColumn::make('income_amount')
                    ->label('Income')
                    ->sortable()
                    ->size('sm')
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->extraAttributes(['style' => 'background-color: #90EE90; color: #000;'])
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') : '0'),

                // Income Change Rate %
                TextColumn::make('income_changerate')
                    ->label('Inc %')
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? $state . '%' : '0%'),

                // Expense Amount - red background like Excel (#FFCCCB)
                TextColumn::make('expence_amount')
                    ->label('Expense')
                    ->sortable()
                    ->size('sm')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                    ->extraAttributes(['style' => 'background-color: #FFCCCB; color: #000;'])
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') : '0'),

                // Expense Change Rate %
                TextColumn::make('expence_changerate')
                    ->label('Exp %')
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? $state . '%' : '0%'),

                // Cashflow Tax Amount
                TextColumn::make('cashflow_tax_amount')
                    ->label('Tax')
                    ->sortable()
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') : '0'),

                // Cashflow Tax Percent
                TextColumn::make('cashflow_tax_percent')
                    ->label('Tax %')
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . '%' : '0%'),

                // Mortgage Term Amount
                TextColumn::make('mortgage_term_amount')
                    ->label('Mortgage Term')
                    ->sortable()
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') : '0'),

                // Mortgage Interest %
                TextColumn::make('mortgage_interest_percent')
                    ->label('Mort %')
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? $state . '%' : '0%'),

                // Mortgage Interest Amount
                TextColumn::make('mortgage_interest_amount')
                    ->label('Interest')
                    ->sortable()
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') : '0'),

                // Mortgage Principal Amount
                TextColumn::make('mortgage_principal_amount')
                    ->label('Principal')
                    ->sortable()
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') : '0'),

                // Mortgage Balance Amount
                TextColumn::make('mortgage_balance_amount')
                    ->label('Balance')
                    ->sortable()
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') : '0'),

                // Asset Market Amount - bold and prominent
                TextColumn::make('asset_market_amount')
                    ->label('Asset Value')
                    ->sortable()
                    ->weight('bold')
                    ->size('sm')
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') : '0'),

                // Asset Change Rate %
                TextColumn::make('asset_changerate_percent')
                    ->label('Asset %')
                    ->sortable()
                    ->size('sm')
                    ->toggleable()
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray'))
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . '%' : '0%'),

                // Asset Equity Amount
                TextColumn::make('asset_equity_amount')
                    ->label('Equity')
                    ->sortable()
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') : '0'),

                // Asset Taxable Amount
                TextColumn::make('asset_taxable_amount')
                    ->label('Taxable')
                    ->sortable()
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') : '0'),

                // Asset Tax Amount
                TextColumn::make('asset_tax_amount')
                    ->label('Asset Tax')
                    ->sortable()
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') : '0'),

                // Realization Amount
                TextColumn::make('realization_amount')
                    ->label('Realization')
                    ->sortable()
                    ->size('sm')
                    ->toggleable()
                    ->extraAttributes(['style' => 'background-color: #FFCCCB; color: #000;'])
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') : '0'),

                // Cashflow After Tax - blue background like Excel (#ADD8E6)
                TextColumn::make('cashflow_after_taxamount')
                    ->label('Cashflow')
                    ->sortable()
                    ->size('sm')
                    ->color(fn ($state) => $state > 0 ? 'info' : ($state < 0 ? 'danger' : 'gray'))
                    ->extraAttributes(['style' => 'background-color: #ADD8E6; color: #000;'])
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') : '0'),

                // FIRE Income Amount
                TextColumn::make('fire_income_amount')
                    ->label('FIRE Income')
                    ->sortable()
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') : '0'),

                // FIRE Expense Amount
                TextColumn::make('fire_expence_amount')
                    ->label('FIRE Expense')
                    ->sortable()
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') : '0'),

                // FIRE Rate %
                TextColumn::make('fire_rate_percent')
                    ->label('FIRE %')
                    ->size('sm')
                    ->toggleable()
                    ->color(fn ($state) => $state >= 100 ? 'success' : ($state >= 75 ? 'warning' : 'danger'))
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . '%' : '0%'),
            ])
            ->filters([
                Filter::make('year_range')
                    ->form([
                        DatePicker::make('from_year')
                            ->label('From Year')
                            ->displayFormat('Y')
                            ->format('Y'),
                        DatePicker::make('to_year')
                            ->label('To Year')
                            ->displayFormat('Y')
                            ->format('Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_year'],
                                fn (Builder $query, $date): Builder => $query->where('year', '>=', date('Y', strtotime($date))),
                            )
                            ->when(
                                $data['to_year'],
                                fn (Builder $query, $date): Builder => $query->where('year', '<=', date('Y', strtotime($date))),
                            );
                    }),

                SelectFilter::make('asset_changerate')
                    ->label('Change Rate Type')
                    ->options([
                        'cash' => 'Cash',
                        'equity' => 'Equity',
                        'bond' => 'Bond',
                        'property' => 'Property',
                        'commodity' => 'Commodity',
                    ])
                    ->multiple(),
            ])
            ->defaultSort('year')
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50, 100, 150]);
    }

    protected function getTableQuery(): Builder
    {
        $query = SimulationAssetYear::query();

        if ($this->simulationAsset) {
            $query->where('asset_id', $this->simulationAsset->id);
        }

        if ($this->simulationConfiguration) {
            $query->where('asset_configuration_id', $this->simulationConfiguration->id);
        }

        return $query->orderBy('year');
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        if ($this->simulationConfiguration) {
            $actions[] = Action::make('back_to_simulation')
                ->label('Back to Simulation')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(route('filament.admin.resources.simulation-configurations.view', [
                    'record' => $this->simulationConfiguration->id,
                    'activeTab' => 'assets'
                ]));
        }

        return $actions;
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];

        $breadcrumbs[route('filament.admin.resources.simulation-configurations.index')] = 'Simulations';

        if ($this->simulationConfiguration) {
            $breadcrumbs[route('filament.admin.pages.simulation-dashboard', [
                'simulation_configuration_id' => $this->simulationConfiguration->id
            ])] = $this->simulationConfiguration->name;

            $breadcrumbs[route('filament.admin.pages.simulation-assets', [
                'simulation_configuration_id' => $this->simulationConfiguration->id
            ])] = 'Assets';
        }

        if ($this->simulationAsset) {
            $breadcrumbs[] = $this->simulationAsset->name; // Current page (no URL)
        }

        return $breadcrumbs;
    }

    protected function getViewData(): array
    {
        return [
            'simulationConfiguration' => $this->simulationConfiguration,
            'simulationAsset' => $this->simulationAsset,
        ];
    }

    public static function getRouteName(?Panel $panel = null): string
    {
        return 'filament.admin.pages.simulation-asset-years';
    }
}
