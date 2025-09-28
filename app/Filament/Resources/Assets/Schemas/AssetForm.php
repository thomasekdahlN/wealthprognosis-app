<?php

namespace App\Filament\Resources\Assets\Schemas;

use App\Models\Asset;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required(),
                RichEditor::make('description')->columnSpanFull()->extraAttributes(['style' => 'min-height: calc(1.5rem * 8);']),
                Select::make('asset_type')
                    ->label('Asset Type')
                    ->options(fn () => \App\Models\AssetType::query()->active()->ordered()->pluck('name', 'type')->all())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, ?string $state): void {
                        $code = (string) ($state ?? '');
                        $type = \App\Models\AssetType::with('taxType')->where('type', $code)->first();
                        $set('asset_type_category', (string) ($type->category ?? '\u2014'));
                        $set('asset_type_tax_type', (string) ($type->taxType->name ?? '\u2014'));
                        $set('asset_type_liquid', (bool) ($type->is_liquid ?? false) ? 'Liquid' : 'Illiquid');

                        $set('asset_can_generate_income', (bool) ($type->can_generate_income ?? false) ? 'Yes' : 'No');
                        $set('asset_can_generate_expenses', (bool) ($type->can_generate_expenses ?? false) ? 'Yes' : 'No');
                        $set('asset_can_have_mortgage', (bool) ($type->can_have_mortgage ?? false) ? 'Yes' : 'No');
                        $set('asset_can_have_market_value', (bool) ($type->can_have_market_value ?? false) ? 'Yes' : 'No');

                    })
                    ->afterStateHydrated(function (\Filament\Schemas\Components\Utilities\Set $set, ?string $state): void {
                        $code = (string) ($state ?? '');
                        $type = \App\Models\AssetType::with('taxType')->where('type', $code)->first();
                        $set('asset_type_category', (string) ($type->category ?? '\u2014'));
                        $set('asset_type_tax_type', (string) ($type->taxType->name ?? '\u2014'));
                        $set('asset_type_liquid', (bool) ($type->is_liquid ?? false) ? 'Liquid' : 'Illiquid');
                        $set('asset_can_generate_income', (bool) ($type->can_generate_income ?? false) ? 'Yes' : 'No');
                        $set('asset_can_generate_expenses', (bool) ($type->can_generate_expenses ?? false) ? 'Yes' : 'No');
                        $set('asset_can_have_mortgage', (bool) ($type->can_have_mortgage ?? false) ? 'Yes' : 'No');
                        $set('asset_can_have_market_value', (bool) ($type->can_have_market_value ?? false) ? 'Yes' : 'No');

                    })
                    ->required(),
                Select::make('group')
                    ->label('Group')
                    ->options(Asset::GROUPS)
                    ->default('private')
                    ->required()
                    ->helperText('Select whether this asset belongs to private or company portfolio'),

                TextInput::make('asset_type_category')
                    ->label('Category')
                    ->prefixIcon('heroicon-o-tag')
                    ->default(function (\Filament\Schemas\Components\Utilities\Get $get): string {
                        $code = (string) ($get('asset_type') ?? '');
                        if ($code === '') {
                            return '';
                        }

                        return (string) (\App\Models\AssetType::query()->where('type', $code)->value('category') ?? '—');
                    })
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('asset_type_tax_type')
                    ->label('Tax Type')
                    ->prefixIcon('heroicon-o-receipt-percent')
                    ->default(function (\Filament\Schemas\Components\Utilities\Get $get): string {
                        $code = (string) ($get('asset_type') ?? '');
                        if ($code === '') {
                            return '';
                        }
                        $type = \App\Models\AssetType::with('taxType')->where('type', $code)->first();

                        return (string) ($type->taxType->name ?? '—');
                    })
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('asset_type_liquid')
                    ->label('Liquidity')
                    ->prefixIcon(function (\Filament\Schemas\Components\Utilities\Get $get): string {
                        $code = (string) ($get('asset_type') ?? '');
                        $isLiquid = (bool) (\App\Models\AssetType::query()->where('type', $code)->value('is_liquid') ?? false);

                        return $isLiquid ? 'heroicon-o-check-circle' : 'heroicon-o-no-symbol';
                    })
                    ->default(function (\Filament\Schemas\Components\Utilities\Get $get): string {
                        $code = (string) ($get('asset_type') ?? '');
                        $isLiquid = (bool) (\App\Models\AssetType::query()->where('type', $code)->value('is_liquid') ?? false);

                        return $isLiquid ? 'Liquid' : 'Illiquid';
                    })
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('asset_can_generate_income')
                    ->label('Can generate income')
                    ->prefixIcon(function (\Filament\Schemas\Components\Utilities\Get $get): string {
                        $code = (string) ($get('asset_type') ?? '');
                        $val = (bool) (\App\Models\AssetType::query()->where('type', $code)->value('can_generate_income') ?? false);

                        return $val ? 'heroicon-o-check-circle' : 'heroicon-o-no-symbol';
                    })
                    ->default(function (\Filament\Schemas\Components\Utilities\Get $get): string {
                        $code = (string) ($get('asset_type') ?? '');
                        $val = (bool) (\App\Models\AssetType::query()->where('type', $code)->value('can_generate_income') ?? false);

                        return $val ? 'Yes' : 'No';
                    })
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('asset_can_generate_expenses')
                    ->label('Can generate expenses')
                    ->prefixIcon(function (\Filament\Schemas\Components\Utilities\Get $get): string {
                        $code = (string) ($get('asset_type') ?? '');
                        $val = (bool) (\App\Models\AssetType::query()->where('type', $code)->value('can_generate_expenses') ?? false);

                        return $val ? 'heroicon-o-check-circle' : 'heroicon-o-no-symbol';
                    })
                    ->default(function (\Filament\Schemas\Components\Utilities\Get $get): string {
                        $code = (string) ($get('asset_type') ?? '');
                        $val = (bool) (\App\Models\AssetType::query()->where('type', $code)->value('can_generate_expenses') ?? false);

                        return $val ? 'Yes' : 'No';
                    })
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('asset_can_have_mortgage')
                    ->label('Can have mortgage')
                    ->prefixIcon(function (\Filament\Schemas\Components\Utilities\Get $get): string {
                        $code = (string) ($get('asset_type') ?? '');
                        $val = (bool) (\App\Models\AssetType::query()->where('type', $code)->value('can_have_mortgage') ?? false);

                        return $val ? 'heroicon-o-check-circle' : 'heroicon-o-no-symbol';
                    })
                    ->default(function (\Filament\Schemas\Components\Utilities\Get $get): string {
                        $code = (string) ($get('asset_type') ?? '');
                        $val = (bool) (\App\Models\AssetType::query()->where('type', $code)->value('can_have_mortgage') ?? false);

                        return $val ? 'Yes' : 'No';
                    })
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('asset_can_have_market_value')
                    ->label('Can have market value')
                    ->prefixIcon(function (\Filament\Schemas\Components\Utilities\Get $get): string {
                        $code = (string) ($get('asset_type') ?? '');
                        $val = (bool) (\App\Models\AssetType::query()->where('type', $code)->value('can_have_market_value') ?? false);

                        return $val ? 'heroicon-o-check-circle' : 'heroicon-o-no-symbol';
                    })
                    ->default(function (\Filament\Schemas\Components\Utilities\Get $get): string {
                        $code = (string) ($get('asset_type') ?? '');
                        $val = (bool) (\App\Models\AssetType::query()->where('type', $code)->value('can_have_market_value') ?? false);

                        return $val ? 'Yes' : 'No';
                    })
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull(),

                Select::make('tax_country')->label('Tax Country')->live()->options(function () {
                    $taxPath = config_path('tax');
                    $countries = [];
                    if (\Illuminate\Support\Facades\File::exists($taxPath)) {
                        foreach (\Illuminate\Support\Facades\File::directories($taxPath) as $dir) {
                            $code = basename($dir);
                            $countries[$code] = match ($code) {
                                'no' => 'Norway',
                                'se' => 'Sweden',
                                'ch' => 'Switzerland',
                                'dk' => 'Denmark',
                                'us' => 'United States',
                                'en' => 'United Kingdom',
                                default => strtoupper($code),
                            };
                        }
                    }

                    return $countries;
                })->default('no')->searchable()->preload()->required(),
                Select::make('tax_property')
                    ->label('Property Tax')
                    ->live()
                    ->options(function (\Filament\Schemas\Components\Utilities\Get $get): array {
                        $country = (string) ($get('tax_country') ?? 'no');
                        $year = (int) date('Y');

                        return \App\Models\TaxProperty::query()
                            ->forCountry($country)
                            ->forYear($year)
                            ->active()
                            ->orderBy('municipality')
                            ->pluck('municipality', 'code')
                            ->all();
                    })
                    ->searchable()
                    ->preload()
                    ->helperText('Select municipality/region specific property tax where applicable'),
                TextInput::make('tax_property_municipality')
                    ->label('Municipality')
                    ->prefixIcon('heroicon-o-building-office-2')
                    ->default(function (\Filament\Schemas\Components\Utilities\Get $get): string {
                        $country = (string) ($get('tax_country') ?? 'no');
                        $code = (string) ($get('tax_property') ?? '');
                        if ($code === '') {
                            return '';
                        }
                        $year = (int) date('Y');

                        return (string) (\App\Models\TaxProperty::query()
                            ->forCountry($country)
                            ->forYear($year)
                            ->where('code', $code)
                            ->value('municipality') ?? '—');
                    })
                    ->disabled()
                    ->dehydrated(false)
                    ->hidden(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => (string) ($get('tax_property') ?? '') === ''),

                TextInput::make('tax_property_home_permill')
                    ->label('Home ‰')
                    ->prefixIcon('heroicon-o-home')
                    ->default(function (\Filament\Schemas\Components\Utilities\Get $get): string {
                        $country = (string) ($get('tax_country') ?? 'no');
                        $code = (string) ($get('tax_property') ?? '');
                        if ($code === '') {
                            return '';
                        }
                        $year = (int) date('Y');

                        return (string) (\App\Models\TaxProperty::query()
                            ->forCountry($country)
                            ->forYear($year)
                            ->where('code', $code)
                            ->value('tax_home_permill') ?? '—');
                    })
                    ->disabled()
                    ->dehydrated(false)
                    ->hidden(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => (string) ($get('tax_property') ?? '') === ''),

                TextInput::make('tax_property_company_permill')
                    ->label('Company ‰')
                    ->prefixIcon('heroicon-o-building-office')
                    ->default(function (\Filament\Schemas\Components\Utilities\Get $get): string {
                        $country = (string) ($get('tax_country') ?? 'no');
                        $code = (string) ($get('tax_property') ?? '');
                        if ($code === '') {
                            return '';
                        }
                        $year = (int) date('Y');

                        return (string) (\App\Models\TaxProperty::query()
                            ->forCountry($country)
                            ->forYear($year)
                            ->where('code', $code)
                            ->value('tax_company_permill') ?? '—');
                    })
                    ->disabled()
                    ->dehydrated(false)
                    ->hidden(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => (string) ($get('tax_property') ?? '') === ''),

                TextInput::make('tax_property_deduction')
                    ->label('Deduction')
                    ->prefixIcon('heroicon-o-banknotes')
                    ->default(function (\Filament\Schemas\Components\Utilities\Get $get): string {
                        $country = (string) ($get('tax_country') ?? 'no');
                        $code = (string) ($get('tax_property') ?? '');
                        if ($code === '') {
                            return '';
                        }
                        $year = (int) date('Y');
                        $ded = \App\Models\TaxProperty::query()
                            ->forCountry($country)
                            ->forYear($year)
                            ->where('code', $code)
                            ->value('deduction');
                        if ($ded === null || (is_numeric($ded) && (float) $ded == 0.0)) {
                            return '—';
                        }

                        return number_format((float) $ded, 0, ',', ' ');
                    })
                    ->disabled()
                    ->dehydrated(false)
                    ->hidden(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => (string) ($get('tax_property') ?? '') === '')
                    ->columnSpanFull(),
                Toggle::make('debug')->label('Debug')->default(false)->helperText('Mark this asset for debugging'),
                Toggle::make('is_active')->default(true)->required(),
            ]);
    }
}
