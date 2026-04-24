<?php

namespace App\Filament\System\Resources\TaxProperty;

use App\Helpers\AmountHelper;
use App\Models\TaxProperty;
use App\Models\TaxProperty as TaxPropertyModel;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TaxPropertyResource extends Resource
{
    protected static ?string $model = TaxPropertyModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static \UnitEnum|string|null $navigationGroup = 'Taxes';

    protected static ?string $navigationLabel = 'Property Taxes';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'municipality';

    protected static ?string $maxContentWidth = 'full';

    public static function getDefaultPage(): string
    {
        return 'index';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $count = TaxPropertyModel::query()->count();

            return (string) $count;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Basic Information')
                ->schema([
                    Select::make('country_code')->label('Country')->options([
                        'no' => 'Norway',
                        'se' => 'Sweden',
                        'ch' => 'Switzerland',
                        'dk' => 'Denmark',
                        'us' => 'United States',
                        'en' => 'United Kingdom',
                    ])->required(),
                    TextInput::make('year')->numeric()->required(),
                    TextInput::make('code')->required(),
                    TextInput::make('municipality')->label('Municipality')->maxLength(128),
                    Toggle::make('has_tax_on_homes')->label('Has tax on homes')->default(false),
                    Toggle::make('has_tax_on_companies')->label('Has tax on companies')->default(false),
                    Toggle::make('is_active')->default(true)->required(),
                ])->columns(2),

            Section::make('Tax Configuration')
                ->schema([
                    TextInput::make('tax_home_permill')->numeric()->label('Home rate (permille)')->helperText('Permille value, e.g. 3.500 for 0.35%')->step(0.001),
                    TextInput::make('tax_company_permill')->numeric()->label('Company rate (permille)')->helperText('Permille value, e.g. 3.500 for 0.35%')->step(0.001),
                    TextInput::make('deduction')->numeric()->label('Deduction (NOK)'),
                    TextInput::make('taxable_percent')->numeric()->label('Taxable percent')->helperText('Percentage of market value that is taxable, e.g. 100.00 for 100%, 80.00 for 80%')->default(100.00)->step(0.01),
                ])->columns(2),

            Section::make('Property Tax Calculation Example')
                ->schema([
                    TextInput::make('dummy_field')
                        ->label('How Property Tax is Calculated')
                        ->helperText(function ($record) {
                            if (! $record) {
                                return 'Save the record to see calculation example.';
                            }

                            $municipality = $record->municipality ?? 'This municipality';
                            $homeRate = $record->tax_home_permill ?? 0;
                            $companyRate = $record->tax_company_permill ?? 0;
                            $deduction = $record->deduction ?? 0;
                            $taxablePercent = $record->taxable_percent ?? 100;

                            $exampleValue = 3000000; // 3 million NOK example
                            $result = "Property tax calculation for {$municipality} (3M NOK property): ";

                            if ($homeRate > 0) {
                                $taxableAmount = ($exampleValue * $taxablePercent / 100);
                                $afterDeduction = max(0, $taxableAmount - $deduction);
                                $tax = $afterDeduction * ($homeRate / 1000);

                                $result .= 'Home tax: '.number_format($tax, 0, ',', ' ')." NOK (Rate: {$homeRate}‰). ";
                            }

                            if ($companyRate > 0) {
                                $taxableAmount = ($exampleValue * $taxablePercent / 100);
                                $afterDeduction = max(0, $taxableAmount - $deduction);
                                $tax = $afterDeduction * ($companyRate / 1000);

                                $result .= 'Company tax: '.number_format($tax, 0, ',', ' ')." NOK (Rate: {$companyRate}‰). ";
                            }

                            if ($homeRate == 0 && $companyRate == 0) {
                                $result .= 'No property tax is levied (rates are 0‰). ';
                            }

                            $result .= "Formula: (Market Value × {$taxablePercent}% - ".number_format($deduction, 0, ',', ' ').' NOK) × Rate‰ ÷ 1000';

                            return $result;
                        })
                        ->disabled()
                        ->dehydrated(false)
                        ->hiddenLabel()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('country_code')->label('Country')->sortable(),
                TextColumn::make('year')->sortable()->alignment(Alignment::End),
                TextColumn::make('code')->sortable()->searchable(),
                TextColumn::make('municipality')->label('Municipality')->toggleable()->sortable(),
                IconColumn::make('has_tax_on_homes')->label('Homes?')->boolean()->toggleable()->sortable(),
                IconColumn::make('has_tax_on_companies')->label('Companies?')->boolean()->toggleable()->sortable(),
                TextColumn::make('tax_home_permill')->label('Home ‰')->toggleable()->sortable()->alignment(Alignment::End)
                    ->formatStateUsing(fn ($state) => $state === null ? '' : number_format((float) $state, 1, ',', ' ')),
                TextColumn::make('tax_company_permill')->label('Company ‰')->toggleable()->sortable()->alignment(Alignment::End)
                    ->formatStateUsing(fn ($state) => $state === null ? '' : number_format((float) $state, 1, ',', ' ')),
                TextColumn::make('deduction')->label('Deduction')->toggleable()->sortable()->alignment(Alignment::End)
                    ->formatStateUsing(fn ($state) => ($state === null || (is_numeric($state) && (float) $state == 0.0)) ? '' : AmountHelper::formatNorwegian((float) $state, 0)),
                TextColumn::make('taxable_percent')->label('Taxable %')->toggleable()->sortable()->alignment(Alignment::End)
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, ',', ' ').' %'),
            ])
            ->filters([
                SelectFilter::make('country_code')
                    ->label('Country')
                    ->options([
                        'no' => 'Norway',
                        'se' => 'Sweden',
                        'ch' => 'Switzerland',
                        'dk' => 'Denmark',
                        'us' => 'United States',
                        'en' => 'United Kingdom',
                    ]),
                SelectFilter::make('year')
                    ->label('Year')
                    ->options(function () {
                        return TaxProperty::query()
                            ->select('year')
                            ->distinct()
                            ->orderBy('year', 'desc')
                            ->pluck('year', 'year')
                            ->toArray();
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->paginationPageOptions([50, 100, 150]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxProperty::route('/'),
            'create' => Pages\CreateTaxProperty::route('/create'),
            'edit' => Pages\EditTaxProperty::route('/{record}/edit'),
        ];
    }
}
