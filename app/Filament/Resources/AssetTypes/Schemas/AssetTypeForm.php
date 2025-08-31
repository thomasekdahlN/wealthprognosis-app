<?php

namespace App\Filament\Resources\AssetTypes\Schemas;

use App\Models\TaxType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AssetTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('type')
                    ->label('Asset Type')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50)
                    ->placeholder('e.g., equityfund, realestate')
                    ->helperText('Unique identifier for this asset type'),

                TextInput::make('name')
                    ->label('Asset Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., Equity Fund, Real Estate'),

                Textarea::make('description')
                    ->label('Description')
                    ->maxLength(1000)
                    ->rows(3)
                    ->placeholder('Detailed description of this asset type'),

                Select::make('category')
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
                    ->searchable()
                    ->placeholder('Select or enter a category'),

                Select::make('tax_type_id')
                    ->label('Tax Type')
                    ->options(TaxType::active()->ordered()->pluck('name', 'id'))
                    ->searchable()
                    ->placeholder('Select applicable tax type')
                    ->helperText('Choose the tax type that applies to this asset type'),

                TextInput::make('icon')
                    ->label('Icon')
                    ->placeholder('heroicon-o-chart-bar')
                    ->helperText('Heroicon class name for display'),

                Select::make('color')
                    ->label('Color Theme')
                    ->options([
                        'gray' => 'Gray',
                        'red' => 'Red',
                        'orange' => 'Orange',
                        'amber' => 'Amber',
                        'yellow' => 'Yellow',
                        'lime' => 'Lime',
                        'green' => 'Green',
                        'emerald' => 'Emerald',
                        'teal' => 'Teal',
                        'cyan' => 'Cyan',
                        'sky' => 'Sky',
                        'blue' => 'Blue',
                        'indigo' => 'Indigo',
                        'violet' => 'Violet',
                        'purple' => 'Purple',
                        'fuchsia' => 'Fuchsia',
                        'pink' => 'Pink',
                        'rose' => 'Rose',
                        'success' => 'Success',
                        'info' => 'Info',
                        'warning' => 'Warning',
                        'danger' => 'Danger',
                        'primary' => 'Primary',
                        'secondary' => 'Secondary',
                    ])
                    ->default('gray')
                    ->searchable()
                    ->helperText('Color theme for badges and visual elements'),

                TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->helperText('Order for display (lower numbers appear first)'),

                Toggle::make('is_private')
                    ->label('Private')
                    ->default(false)
                    ->helperText('Available for private individuals'),

                Toggle::make('is_company')
                    ->label('Company')
                    ->default(false)
                    ->helperText('Available for companies'),

                Toggle::make('is_tax_optimized')
                    ->label('Tax Optimized')
                    ->default(false)
                    ->helperText('Has special tax advantages or considerations'),

                Toggle::make('is_fire_sellable')
                    ->label('FIRE Sellable')
                    ->default(false)
                    ->helperText('Can be partially sold to fund Financial Independence/Retire Early lifestyle'),

                Toggle::make('can_generate_income')
                    ->label('Can Generate Income')
                    ->default(false)
                    ->helperText('This asset type can generate income (e.g., rent, dividends).'),

                Toggle::make('can_generate_expenses')
                    ->label('Can Generate Expenses')
                    ->default(false)
                    ->helperText('This asset type can have running costs (e.g., maintenance, fees).'),

                Toggle::make('can_have_mortgage')
                    ->label('Can Have Mortgage')
                    ->default(false)
                    ->helperText('This asset type can be financed with a mortgage or loan.'),

                Toggle::make('can_have_market_value')
                    ->label('Can Have Market Value')
                    ->default(false)
                    ->helperText('This asset type has a market value to track.'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Whether this asset type is currently in use'),
            ]);
    }
}
