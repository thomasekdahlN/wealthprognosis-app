<?php

namespace App\Filament\Resources\AssetYears\Schemas;

use App\Filament\Resources\AssetYears\Pages\CreateAssetYear as CreateAssetYearPage;
use App\Helpers\AmountHelper;
use App\Models\PrognosisChangeRate;
use App\Rules\AssetRuleValidation;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class AssetYearForm
{
    /**
     * Get changerate options for dropdowns
     */
    private static function getChangeRateOptions(): array
    {
        // Get distinct asset types from prognosis_change_rates table
        $distinctAssetTypes = PrognosisChangeRate::query()
            ->select('asset_type')
            ->distinct()
            ->active()
            ->pluck('asset_type')
            ->toArray();

        $options = [];

        // Build options array with 'changerates.asset_type' format
        foreach ($distinctAssetTypes as $assetType) {
            $key = "changerates.{$assetType}";

            // Try to get name from AssetType model first, fallback to constants
            $assetTypeModel = \App\Models\AssetType::where('type', $assetType)->first();
            if ($assetTypeModel) {
                $label = $assetTypeModel->name;
            } else {
                $label = ucfirst($assetType);
            }

            $options[$key] = $label;
        }

        // Sort by label
        asort($options);

        return $options;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Year & Assignment')
                ->schema([
                    TextInput::make('year')->numeric()->required()->minValue(1925)->maxValue(2125)->helperText('Calendar year this record applies to.')->columnSpanFull(),
                    Select::make('asset_id')
                        ->relationship('asset', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Asset this year belongs to (shown on create).')
                        ->visible(fn ($livewire) => $livewire instanceof CreateAssetYearPage)
                        ->columnSpan(3),
                    Select::make('asset_configuration_id')
                        ->relationship('assetConfiguration', 'name')
                        ->searchable()
                        ->preload()
                        ->helperText('Configuration/owner (shown on create).')
                        ->visible(fn ($livewire) => $livewire instanceof CreateAssetYearPage)
                        ->columnSpan(3),
                ])
                ->columns(12)
                ->columnSpanFull(),

            Grid::make(2)->schema([
                Section::make('Income')
                    ->schema([
                        RichEditor::make('income_description')->columnSpanFull()->extraAttributes(['style' => 'min-height: calc(1.5rem * 8);'])->helperText('Describe the expected income for this year (e.g., salary, rent).'),
                        TextInput::make('income_amount')->numeric()->extraAttributes(AmountHelper::getNorwegianAmountMask())->mask(AmountHelper::getAlpineAmountMask())->stripCharacters([' '])->suffix('NOK')->helperText('Gross income amount for the year (masked, NOK).'),
                        Select::make('income_factor')
                            ->label('Income Factor')
                            ->options(\App\Models\AssetYear::getFactorOptions())
                            ->required()
                            ->default('yearly')
                            ->helperText('Choose whether the income amount is monthly or yearly.'),
                        TextInput::make('income_rule')->helperText('Calculation rule for income. Click ? for examples.')
                            ->label('Income Rule')
                            ->rules([new AssetRuleValidation])
                            ->helperText('Enter calculation rule. Examples: +10% (add 10%), -5% (subtract 5%), +1000 (add 1000), +1/10 (add 1/10th yearly), +1|10 (decreasing fraction)')
                            ->suffixAction(
                                Action::make('rule_help')
                                    ->icon('heroicon-o-question-mark-circle')
                                    ->tooltip('Rule Format Help')
                                    ->modalHeading('Rule format examples')
                                    ->modalDescription(new HtmlString(
                                        '<div class="space-y-2">'
                                        .'<div><strong>+10%</strong> — Add 10% to the amount</div>'
                                        .'<div><strong>-10%</strong> — Subtract 10% from the amount</div>'
                                        .'<div><strong>10%</strong> — Calculate 10% of the amount</div>'
                                        .'<div><strong>+1000</strong> — Add 1000 to the amount</div>'
                                        .'<div><strong>-1000</strong> — Subtract 1000 from the amount</div>'
                                        .'<div><strong>+1/10</strong> — Add 1/10 of the amount yearly</div>'
                                        .'<div><strong>-1/10</strong> — Subtract 1/10 of the amount yearly</div>'
                                        .'<div><strong>1/10</strong> — Calculate 1/10 of the amount (does not change it)</div>'
                                        .'<div><strong>+1|10</strong> — Add 1/10 yearly, decreasing denominator (1/10 → 1/9 → 1/8 ...)</div>'
                                        .'<div><strong>-1|10</strong> — Subtract 1/10 yearly, decreasing denominator — useful to empty an asset over 10 years</div>'
                                        .'<div><strong>1|10</strong> — Calculate 1|10 of the amount (does not change it)</div>'
                                        .'</div>'
                                    ))
                                    ->modalSubmitAction(false)
                                    ->modalCancelAction(fn ($action) => $action->label('Close'))
                            ),
                        Select::make('income_transfer')
                            ->helperText('Transfer income to the next asset (higher sort order).')
                            ->label('Income Transfer')
                            ->searchable()
                            ->options(function (Get $get): array {
                                $assetId = $get('asset_id') ?: optional(\App\Models\AssetYear::find(request()->route('record')))->asset_id ?: request()->get('asset');
                                if (! $assetId) {
                                    return [];
                                }
                                $current = \App\Models\Asset::query()->find($assetId);
                                if (! $current || ! $current->asset_configuration_id) {
                                    return [];
                                }
                                $assets = \App\Models\Asset::query()
                                    ->where('asset_configuration_id', $current->asset_configuration_id)
                                    ->where('sort_order', '>', $current->sort_order)
                                    ->orderBy('sort_order')
                                    ->get(['id', 'name', 'asset_type']);
                                $built = [];
                                foreach ($assets as $a) {
                                    $prefix = $a->asset_type;
                                    $built[$prefix.'.$year.income.amount'] = $a->name;
                                }

                                return $built;
                            })
                            ->nullable()
                            ->default(null)
                            ->helperText('Transfer income to another asset. Select from assets with higher sort order within the same configuration.'),
                        Select::make('income_source')
                            ->helperText('Use income from a previous asset (lower sort order).')
                            ->label('Income Source')
                            ->searchable()
                            ->options(function (Get $get): array {
                                $assetId = $get('asset_id') ?: optional(\App\Models\AssetYear::find(request()->route('record')))->asset_id ?: request()->get('asset');
                                if (! $assetId) {
                                    return [];
                                }
                                $current = \App\Models\Asset::query()->find($assetId);
                                if (! $current || ! $current->asset_configuration_id) {
                                    return [];
                                }
                                $assets = \App\Models\Asset::query()
                                    ->where('asset_configuration_id', $current->asset_configuration_id)
                                    ->where('sort_order', '<', $current->sort_order)
                                    ->orderBy('sort_order')
                                    ->get(['id', 'name', 'asset_type']);
                                $built = [];
                                foreach ($assets as $a) {
                                    $prefix = $a->asset_type;
                                    $built[$prefix.'.$year.income.amount'] = $a->name;
                                }

                                return $built;
                            })
                            ->nullable()
                            ->default(null)
                            ->helperText('Source of income from another asset. Select from assets with lower sort order within the same configuration.'),
                        TextInput::make('income_changerate')
                            ->label('Income Change Rate')
                            ->placeholder('e.g. 2.5 or changerates.kpi')
                            ->suffix('%')
                            ->datalist(array_keys(self::getChangeRateOptions()))
                            ->nullable()
                            ->default(null)
                            ->rule('nullable|regex:/^([-+]?\\d+(\\.\\d+)?|changerates\\.[a-z0-9_\\-]+)$/i')
                            ->helperText('Type a decimal percent (e.g. 2.5) or pick a predefined rate like changerates.kpi'),
                        Toggle::make('income_repeat')->label('Repeat Income')->helperText('If on, this income setup repeats into future years.'),
                    ])
                    ->visible(function ($get): bool {
                        $assetId = $get('asset_id') ?: optional(\App\Models\AssetYear::find(request()->route('record')))->asset_id ?: request()->get('asset');
                        if (! $assetId) {
                            return true;
                        }
                        $asset = \App\Models\Asset::with('assetType')->find($assetId);

                        return (bool) ($asset?->assetType?->can_generate_income ?? true);
                    }),

                Section::make('Expense')
                    ->schema([
                        RichEditor::make('expence_description')->columnSpanFull()->extraAttributes(['style' => 'min-height: calc(1.5rem * 8);'])->helperText('Describe the expected expenses for this year.'),
                        TextInput::make('expence_amount')->numeric()->extraAttributes(AmountHelper::getNorwegianAmountMask())->mask(AmountHelper::getAlpineAmountMask())->stripCharacters([' '])->suffix('NOK')->helperText('Total expenses for the year (NOK).'),
                        Select::make('expence_factor')
                            ->label('Expense Factor')
                            ->options(\App\Models\AssetYear::getFactorOptions())
                            ->required()
                            ->default('yearly')
                            ->helperText('Choose whether the expense amount is monthly or yearly'),
                        TextInput::make('expence_rule')
                            ->label('Expense Rule')
                            ->rules([new AssetRuleValidation])
                            ->helperText('Calculation rule for expenses. Click ? for examples.')
                            ->suffixAction(
                                Action::make('rule_help')
                                    ->icon('heroicon-o-question-mark-circle')
                                    ->tooltip('Rule Format Help')
                                    ->modalHeading('Rule format examples')
                                    ->modalDescription(new HtmlString(
                                        '<div class="space-y-2">'
                                        .'<div><strong>+10%</strong> — Add 10% to the amount</div>'
                                        .'<div><strong>-10%</strong> — Subtract 10% from the amount</div>'
                                        .'<div><strong>10%</strong> — Calculate 10% of the amount</div>'
                                        .'<div><strong>+1000</strong> — Add 1000 to the amount</div>'
                                        .'<div><strong>-1000</strong> — Subtract 1000 from the amount</div>'
                                        .'<div><strong>+1/10</strong> — Add 1/10 of the amount yearly</div>'
                                        .'<div><strong>-1/10</strong> — Subtract 1/10 of the amount yearly</div>'
                                        .'<div><strong>1/10</strong> — Calculate 1/10 of the amount (does not change it)</div>'
                                        .'<div><strong>+1|10</strong> — Add 1/10 yearly, decreasing denominator (1/10 → 1/9 → 1/8 ...)</div>'
                                        .'<div><strong>-1|10</strong> — Subtract 1/10 yearly, decreasing denominator — useful to empty an asset over 10 years</div>'
                                        .'<div><strong>1|10</strong> — Calculate 1|10 of the amount (does not change it)</div>'
                                        .'</div>'
                                    ))
                                    ->modalSubmitAction(false)
                                    ->modalCancelAction(fn ($action) => $action->label('Close'))
                            ),
                        Select::make('expence_transfer')
                            ->helperText('Transfer expense to the next asset (higher sort order).')
                            ->label('Expense Transfer')
                            ->searchable()
                            ->options(function (Get $get): array {
                                $assetId = $get('asset_id') ?: optional(\App\Models\AssetYear::find(request()->route('record')))->asset_id ?: request()->get('asset');
                                if (! $assetId) {
                                    return [];
                                }
                                $current = \App\Models\Asset::query()->find($assetId);
                                if (! $current || ! $current->asset_configuration_id) {
                                    return [];
                                }
                                $assets = \App\Models\Asset::query()
                                    ->where('asset_configuration_id', $current->asset_configuration_id)
                                    ->where('sort_order', '>', $current->sort_order)
                                    ->orderBy('sort_order')
                                    ->get(['id', 'name', 'asset_type']);
                                $built = [];
                                foreach ($assets as $a) {
                                    $prefix = $a->asset_type;
                                    $built[$prefix.'.$year.expence.amount'] = $a->name;
                                }

                                return $built;
                            })
                            ->nullable()
                            ->default(null)
                            ->helperText('Transfer expense to another asset. Select from assets with higher sort order within the same configuration.'),
                        Select::make('expence_source')
                            ->helperText('Use expense from a previous asset (lower sort order).')
                            ->label('Expense Source')
                            ->searchable()
                            ->options(function (Get $get): array {
                                $assetId = $get('asset_id') ?: optional(\App\Models\AssetYear::find(request()->route('record')))->asset_id ?: request()->get('asset');
                                if (! $assetId) {
                                    return [];
                                }
                                $current = \App\Models\Asset::query()->find($assetId);
                                if (! $current || ! $current->asset_configuration_id) {
                                    return [];
                                }
                                $assets = \App\Models\Asset::query()
                                    ->where('asset_configuration_id', $current->asset_configuration_id)
                                    ->where('sort_order', '<', $current->sort_order)
                                    ->orderBy('sort_order')
                                    ->get(['id', 'name', 'asset_type']);
                                $built = [];
                                foreach ($assets as $a) {
                                    $prefix = $a->asset_type;
                                    $built[$prefix.'.$year.expence.amount'] = $a->name;
                                }

                                return $built;
                            })
                            ->nullable()
                            ->default(null)
                            ->helperText('Source of expense from another asset. Select from assets with lower sort order within the same configuration.'),
                        TextInput::make('expence_changerate')
                            ->label('Expense Change Rate')
                            ->placeholder('e.g. 2.5 or changerates.kpi')
                            ->suffix('%')
                            ->datalist(array_keys(self::getChangeRateOptions()))
                            ->nullable()
                            ->default(null)
                            ->rule('nullable|regex:/^([-+]?\\d+(\\.\\d+)?|changerates\\.[a-z0-9_\\-]+)$/i')
                            ->helperText('Type a decimal percent (e.g. 2.5) or pick a predefined rate like changerates.kpi'),
                        Toggle::make('expence_repeat')->label('Repeat Expense')->helperText('If on, this expense setup repeats into future years.'),
                    ])
                    ->visible(function ($get): bool {
                        $assetId = $get('asset_id') ?: optional(\App\Models\AssetYear::find(request()->route('record')))->asset_id ?: request()->get('asset');
                        if (! $assetId) {
                            return true;
                        }
                        $asset = \App\Models\Asset::with('assetType')->find($assetId);

                        return (bool) ($asset?->assetType?->can_generate_expenses ?? true);
                    }),
            ]),

            Grid::make(2)->schema([
                Section::make('Asset')
                    ->schema([
                        TextInput::make('asset_name')->helperText('Optional label for this asset snapshot.'),
                        RichEditor::make('asset_description')->columnSpanFull()->extraAttributes(['style' => 'min-height: calc(1.5rem * 8);'])->helperText('Describe the asset state this year.'),
                        TextInput::make('asset_market_amount')->numeric()->extraAttributes(AmountHelper::getNorwegianAmountMask())->mask(AmountHelper::getAlpineAmountMask())->stripCharacters([' '])->suffix('NOK')->helperText('Market value (NOK).'),
                        TextInput::make('asset_acquisition_amount')->numeric()->extraAttributes(AmountHelper::getNorwegianAmountMask())->mask(AmountHelper::getAlpineAmountMask())->stripCharacters([' '])->suffix('NOK')->helperText('Acquisition price (NOK).'),
                        TextInput::make('asset_equity_amount')->numeric()->extraAttributes(AmountHelper::getNorwegianAmountMask())->mask(AmountHelper::getAlpineAmountMask())->stripCharacters([' '])->suffix('NOK')->helperText('Equity value (NOK).'),
                        TextInput::make('asset_taxable_initial_amount')->numeric()->extraAttributes(AmountHelper::getNorwegianAmountMask())->mask(AmountHelper::getAlpineAmountMask())->stripCharacters([' '])->suffix('NOK')->helperText('Taxable initial amount (NOK).'),
                        TextInput::make('asset_paid_amount')->numeric()->extraAttributes(AmountHelper::getNorwegianAmountMask())->mask(AmountHelper::getAlpineAmountMask())->stripCharacters([' '])->suffix('NOK')->helperText('Amount paid this year (NOK).'),
                        TextInput::make('asset_changerate')
                            ->label('Asset Change Rate')
                            ->placeholder('e.g. 2.5 or changerates.kpi')
                            ->suffix('%')
                            ->datalist(array_keys(self::getChangeRateOptions()))
                            ->nullable()
                            ->default(null)
                            ->rule('nullable|regex:/^([-+]?\\d+(\\.\\d+)?|changerates\\.[a-z0-9_\\-]+)$/i')
                            ->helperText('Type a decimal percent (e.g. 2.5) or pick a predefined rate like changerates.kpi'),
                        TextInput::make('asset_rule')
                            ->label('Asset Rule')
                            ->rules([new AssetRuleValidation])
                            ->helperText('Calculation rule for asset value. Click ? for examples.')
                            ->suffixAction(
                                Action::make('rule_help')
                                    ->icon('heroicon-o-question-mark-circle')
                                    ->tooltip('Rule Format Help')
                                    ->modalHeading('Rule format examples')
                                    ->modalDescription(new HtmlString(
                                        '<div class="space-y-2">'
                                        .'<div><strong>+10%</strong> — Add 10% to the amount</div>'
                                        .'<div><strong>-10%</strong> — Subtract 10% from the amount</div>'
                                        .'<div><strong>10%</strong> — Calculate 10% of the amount</div>'
                                        .'<div><strong>+1000</strong> — Add 1000 to the amount</div>'
                                        .'<div><strong>-1000</strong> — Subtract 1000 from the amount</div>'
                                        .'<div><strong>+1/10</strong> — Add 1/10 of the amount yearly</div>'
                                        .'<div><strong>-1/10</strong> — Subtract 1/10 of the amount yearly</div>'
                                        .'<div><strong>1/10</strong> — Calculate 1/10 of the amount (does not change it)</div>'
                                        .'<div><strong>+1|10</strong> — Add 1/10 yearly, decreasing denominator (1/10 → 1/9 → 1/8 ...)</div>'
                                        .'<div><strong>-1|10</strong> — Subtract 1/10 yearly, decreasing denominator — useful to empty an asset over 10 years</div>'
                                        .'<div><strong>1|10</strong> — Calculate 1|10 of the amount (does not change it)</div>'
                                        .'</div>'
                                    ))
                                    ->modalSubmitAction(false)
                                    ->modalCancelAction(fn ($action) => $action->label('Close'))
                            ),
                        Select::make('asset_transfer')
                            ->helperText('Transfer market value to a later asset (higher sort order).')
                            ->label('Asset Transfer')
                            ->searchable()
                            ->options(function (Get $get): array {
                                $assetId = $get('asset_id') ?: optional(\App\Models\AssetYear::find(request()->route('record')))->asset_id ?: request()->get('asset');
                                if (! $assetId) {
                                    return [];
                                }
                                $current = \App\Models\Asset::query()->find($assetId);
                                if (! $current || ! $current->asset_configuration_id) {
                                    return [];
                                }
                                $assets = \App\Models\Asset::query()
                                    ->where('asset_configuration_id', $current->asset_configuration_id)
                                    ->where('sort_order', '>', $current->sort_order)
                                    ->orderBy('sort_order')
                                    ->get(['id', 'name', 'asset_type']);
                                $built = [];
                                foreach ($assets as $a) {
                                    $prefix = $a->asset_type;
                                    $built[$prefix.'.$year.asset.amount'] = $a->name;
                                }

                                return $built;
                            })
                            ->nullable()
                            ->default(null)
                            ->helperText('Transfer asset value to another asset. Select from assets with higher sort order within the same configuration.'),
                        Select::make('asset_source')
                            ->helperText('Use market value from an earlier asset (lower sort order).')
                            ->label('Asset Source')
                            ->searchable()
                            ->options(function (Get $get): array {
                                $assetId = $get('asset_id') ?: optional(\App\Models\AssetYear::find(request()->route('record')))->asset_id ?: request()->get('asset');
                                if (! $assetId) {
                                    return [];
                                }
                                $current = \App\Models\Asset::query()->find($assetId);
                                if (! $current || ! $current->asset_configuration_id) {
                                    return [];
                                }
                                $assets = \App\Models\Asset::query()
                                    ->where('asset_configuration_id', $current->asset_configuration_id)
                                    ->where('sort_order', '<', $current->sort_order)
                                    ->orderBy('sort_order')
                                    ->get(['id', 'name', 'asset_type']);
                                $built = [];
                                foreach ($assets as $a) {
                                    $prefix = $a->asset_type;
                                    $built[$prefix.'.$year.asset.amount'] = $a->name;
                                }

                                return $built;
                            })
                            ->nullable()
                            ->default(null)
                            ->helperText('Source of asset value from another asset. Select from assets with lower sort order within the same configuration.'),
                        Toggle::make('asset_repeat')->label('Repeat Asset')->helperText('If on, this asset values setup repeats into future years.'),
                    ])
                    ->visible(function ($get): bool {
                        $assetId = $get('asset_id') ?: optional(\App\Models\AssetYear::find(request()->route('record')))->asset_id ?: request()->get('asset');
                        if (! $assetId) {
                            return true;
                        }
                        $asset = \App\Models\Asset::with('assetType')->find($assetId);

                        return (bool) ($asset?->assetType?->can_have_market_value ?? true);
                    }),

                Section::make('Mortgage')
                    ->schema([
                        TextInput::make('mortgage_name')->helperText('Optional label for this mortgage event.'),
                        RichEditor::make('mortgage_description')->columnSpanFull()->extraAttributes(['style' => 'min-height: calc(1.5rem * 8);'])->helperText('Describe mortgage changes this year (e.g., refinance, extra payment).'),
                        TextInput::make('mortgage_amount')->numeric()->extraAttributes(AmountHelper::getNorwegianAmountMask())->mask(AmountHelper::getAlpineAmountMask())->stripCharacters([' '])->suffix('NOK')->helperText('Outstanding principal (NOK).'),
                        TextInput::make('mortgage_years')->numeric()->helperText('Remaining years of the loan.'),
                        TextInput::make('mortgage_interest')->suffix('%')->helperText('Annual interest rate (e.g., 4.5).'),
                        TextInput::make('mortgage_interest_only_years')->numeric()->helperText('Number of interest-only years remaining.'),
                        TextInput::make('mortgage_extra_downpayment_amount')->numeric()->extraAttributes(AmountHelper::getNorwegianAmountMask())->mask(AmountHelper::getAlpineAmountMask())->stripCharacters([' '])->suffix('NOK')->helperText('Extra principal payment this year (NOK).'),
                        TextInput::make('mortgage_gebyr')->numeric()->extraAttributes(AmountHelper::getNorwegianAmountMask())->mask(AmountHelper::getAlpineAmountMask())->stripCharacters([' '])->suffix('NOK')->helperText('Fees (NOK).'),
                        TextInput::make('mortgage_tax')->numeric()->helperText('Tax deduction rate for interest (if applicable).'),
                    ])
                    ->visible(function ($get): bool {
                        $assetId = $get('asset_id') ?: optional(\App\Models\AssetYear::find(request()->route('record')))->asset_id ?: request()->get('asset');
                        if (! $assetId) {
                            return true;
                        }
                        $asset = \App\Models\Asset::with('assetType')->find($assetId);

                        return (bool) ($asset?->assetType?->can_have_mortgage ?? true);
                    }),
            ]),
        ]);
    }
}
