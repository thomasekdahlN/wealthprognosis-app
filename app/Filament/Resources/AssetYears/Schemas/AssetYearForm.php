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
                    TextInput::make('year')->numeric()->required()->minValue(1925)->maxValue(2125)->columnSpanFull(),
                    Select::make('asset_id')
                        ->relationship('asset', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->visible(fn ($livewire) => $livewire instanceof CreateAssetYearPage)
                        ->columnSpan(3),
                    Select::make('asset_configuration_id')
                        ->relationship('assetConfiguration', 'name')
                        ->searchable()
                        ->preload()
                        ->visible(fn ($livewire) => $livewire instanceof CreateAssetYearPage)
                        ->columnSpan(3),
                ])
                ->columns(12)
                ->columnSpanFull(),

            Grid::make(2)->schema([
                Section::make('Income')
                    ->schema([
                        RichEditor::make('income_description')->columnSpanFull()->extraAttributes(['style' => 'min-height: calc(1.5rem * 8);']),
                        TextInput::make('income_amount')->numeric()->extraAttributes(AmountHelper::getNorwegianAmountMask())->mask(AmountHelper::getAlpineAmountMask())->stripCharacters([' '])->suffix('NOK'),
                        Select::make('income_factor')
                            ->label('Income Factor')
                            ->options(\App\Models\AssetYear::getFactorOptions())
                            ->required()
                            ->default('yearly')
                            ->helperText('Choose whether the income amount is monthly or yearly'),
                        TextInput::make('income_rule')
                            ->label('Income Rule')
                            ->rules([new AssetRuleValidation])
                            ->helperText('Enter calculation rule. Examples: +10% (add 10%), -5% (subtract 5%), +1000 (add 1000), +1/10 (add 1/10th yearly), +1|10 (decreasing fraction)')
                            ->suffixAction(
                                Action::make('rule_help')
                                    ->icon('heroicon-o-question-mark-circle')
                                    ->tooltip('Rule Format Help')
                                    ->modalHeading('Rule Format Examples')
                                    ->modalDescription(
                                        '+10% - Adds 10% to amount'."\n".
                                        '-10% - Subtracts 10% from amount'."\n".
                                        '10% - Calculates 10% of the amount'."\n".
                                        '+1000 - Adds 1000 to amount'."\n".
                                        '-1000 - Subtracts 1000 from amount'."\n".
                                        '+1/10 - Adds 1 tenth of the amount yearly'."\n".
                                        '-1/10 - Subtracts 1 tenth of the amount yearly'."\n".
                                        '1/10 - Calculates 1/10 of the amount. Does not change the amount'."\n".
                                        '+1|10 - Adds 1 tenth of the amount yearly, and subtracts nevner with one (so next value is 1/9, then 1/8, 1/7 etc)'."\n".
                                        '-1|10 - Subtracts 1 tenth of the amount yearly. Then subtracts nevner with one. (so next value is 1/9, then 1/8, 1/7 etc). Perfect for usage to empty an asset over 10 years.'."\n".
                                        '1|10 - Calculates 1|10 of the amount. Does not change the amount.'
                                    )
                                    ->modalSubmitAction(false)
                                    ->modalCancelAction(fn ($action) => $action->label('Close'))
                            ),
                        Select::make('income_transfer')
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
                        Select::make('income_changerate')
                            ->label('Income Change Rate')
                            ->options(self::getChangeRateOptions())
                            ->default('changerates.kpi')
                            ->searchable()
                            ->helperText('Select the change rate type for income calculations'),
                        Toggle::make('income_repeat')->label('Repeat Income'),
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
                        RichEditor::make('expence_description')->columnSpanFull()->extraAttributes(['style' => 'min-height: calc(1.5rem * 8);']),
                        TextInput::make('expence_amount')->numeric()->extraAttributes(AmountHelper::getNorwegianAmountMask())->mask(AmountHelper::getAlpineAmountMask())->stripCharacters([' '])->suffix('NOK'),
                        Select::make('expence_factor')
                            ->label('Expense Factor')
                            ->options(\App\Models\AssetYear::getFactorOptions())
                            ->required()
                            ->default('yearly')
                            ->helperText('Choose whether the expense amount is monthly or yearly'),
                        TextInput::make('expence_rule')
                            ->label('Expense Rule')
                            ->rules([new AssetRuleValidation])
                            ->helperText('Enter calculation rule. Examples: +10% (add 10%), -5% (subtract 5%), +1000 (add 1000), +1/10 (add 1/10th yearly), +1|10 (decreasing fraction)')
                            ->suffixAction(
                                Action::make('rule_help')
                                    ->icon('heroicon-o-question-mark-circle')
                                    ->tooltip('Rule Format Help')
                                    ->modalHeading('Rule Format Examples')
                                    ->modalDescription(
                                        '+10% - Adds 10% to amount'."\n".
                                        '-10% - Subtracts 10% from amount'."\n".
                                        '10% - Calculates 10% of the amount'."\n".
                                        '+1000 - Adds 1000 to amount'."\n".
                                        '-1000 - Subtracts 1000 from amount'."\n".
                                        '+1/10 - Adds 1 tenth of the amount yearly'."\n".
                                        '-1/10 - Subtracts 1 tenth of the amount yearly'."\n".
                                        '1/10 - Calculates 1/10 of the amount. Does not change the amount'."\n".
                                        '+1|10 - Adds 1 tenth of the amount yearly, and subtracts nevner with one (so next value is 1/9, then 1/8, 1/7 etc)'."\n".
                                        '-1|10 - Subtracts 1 tenth of the amount yearly. Then subtracts nevner with one. (so next value is 1/9, then 1/8, 1/7 etc). Perfect for usage to empty an asset over 10 years.'."\n".
                                        '1|10 - Calculates 1|10 of the amount. Does not change the amount.'
                                    )
                                    ->modalSubmitAction(false)
                                    ->modalCancelAction(fn ($action) => $action->label('Close'))
                            ),
                        Select::make('expence_transfer')
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
                        Select::make('expence_changerate')
                            ->label('Expense Change Rate')
                            ->options(self::getChangeRateOptions())
                            ->default('changerates.kpi')
                            ->searchable()
                            ->helperText('Select the change rate type for expense calculations'),
                        Toggle::make('expence_repeat')->label('Repeat Expense'),
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
                        TextInput::make('asset_name'),
                        RichEditor::make('asset_description')->columnSpanFull()->extraAttributes(['style' => 'min-height: calc(1.5rem * 8);']),
                        TextInput::make('asset_market_amount')->numeric()->extraAttributes(AmountHelper::getNorwegianAmountMask())->mask(AmountHelper::getAlpineAmountMask())->stripCharacters([' '])->suffix('NOK'),
                        TextInput::make('asset_acquisition_amount')->numeric()->extraAttributes(AmountHelper::getNorwegianAmountMask())->mask(AmountHelper::getAlpineAmountMask())->stripCharacters([' '])->suffix('NOK'),
                        TextInput::make('asset_equity_amount')->numeric()->extraAttributes(AmountHelper::getNorwegianAmountMask())->mask(AmountHelper::getAlpineAmountMask())->stripCharacters([' '])->suffix('NOK'),
                        TextInput::make('asset_taxable_initial_amount')->numeric()->extraAttributes(AmountHelper::getNorwegianAmountMask())->mask(AmountHelper::getAlpineAmountMask())->stripCharacters([' '])->suffix('NOK'),
                        TextInput::make('asset_paid_amount')->numeric()->extraAttributes(AmountHelper::getNorwegianAmountMask())->mask(AmountHelper::getAlpineAmountMask())->stripCharacters([' '])->suffix('NOK'),
                        Select::make('asset_changerate')
                            ->label('Asset Change Rate')
                            ->options(self::getChangeRateOptions())
                            ->default('changerates.kpi')
                            ->searchable()
                            ->helperText('Select the change rate type for asset value calculations'),
                        TextInput::make('asset_rule')
                            ->label('Asset Rule')
                            ->rules([new AssetRuleValidation])
                            ->helperText('Enter calculation rule. Examples: +10% (add 10%), -5% (subtract 5%), +1000 (add 1000), +1/10 (add 1/10th yearly), +1|10 (decreasing fraction)')
                            ->suffixAction(
                                Action::make('rule_help')
                                    ->icon('heroicon-o-question-mark-circle')
                                    ->tooltip('Rule Format Help')
                                    ->modalHeading('Rule Format Examples')
                                    ->modalDescription(
                                        '+10% - Adds 10% to amount'."\n".
                                        '-10% - Subtracts 10% from amount'."\n".
                                        '10% - Calculates 10% of the amount'."\n".
                                        '+1000 - Adds 1000 to amount'."\n".
                                        '-1000 - Subtracts 1000 from amount'."\n".
                                        '+1/10 - Adds 1 tenth of the amount yearly'."\n".
                                        '-1/10 - Subtracts 1 tenth of the amount yearly'."\n".
                                        '1/10 - Calculates 1/10 of the amount. Does not change the amount'."\n".
                                        '+1|10 - Adds 1 tenth of the amount yearly, and subtracts nevner with one (so next value is 1/9, then 1/8, 1/7 etc)'."\n".
                                        '-1|10 - Subtracts 1 tenth of the amount yearly. Then subtracts nevner with one. (so next value is 1/9, then 1/8, 1/7 etc). Perfect for usage to empty an asset over 10 years.'."\n".
                                        '1|10 - Calculates 1|10 of the amount. Does not change the amount.'
                                    )
                                    ->modalSubmitAction(false)
                                    ->modalCancelAction(fn ($action) => $action->label('Close'))
                            ),
                        Select::make('asset_transfer')
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
                        Toggle::make('asset_repeat')->label('Repeat Asset'),
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
                        TextInput::make('mortgage_name'),
                        RichEditor::make('mortgage_description')->columnSpanFull()->extraAttributes(['style' => 'min-height: calc(1.5rem * 8);']),
                        TextInput::make('mortgage_amount')->numeric()->extraAttributes(AmountHelper::getNorwegianAmountMask())->mask(AmountHelper::getAlpineAmountMask())->stripCharacters([' '])->suffix('NOK'),
                        TextInput::make('mortgage_years')->numeric(),
                        TextInput::make('mortgage_interest'),
                        TextInput::make('mortgage_interest_only_years')->numeric(),
                        TextInput::make('mortgage_extra_downpayment_amount'),
                        TextInput::make('mortgage_gebyr')->numeric()->extraAttributes(AmountHelper::getNorwegianAmountMask())->mask(AmountHelper::getAlpineAmountMask())->stripCharacters([' '])->suffix('NOK'),
                        TextInput::make('mortgage_tax')->numeric(),
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
