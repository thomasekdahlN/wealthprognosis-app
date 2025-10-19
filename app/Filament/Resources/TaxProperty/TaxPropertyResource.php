<?php

namespace App\Filament\Resources\TaxProperty;

use App\Helpers\AmountHelper;
use App\Models\TaxProperty as TaxPropertyModel;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
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
            // Norway (per user spec)
            TextInput::make('municipality')->label('Municipality')->maxLength(128),
            Toggle::make('has_tax_on_homes')->label('Has tax on homes')->default(false),
            Toggle::make('has_tax_on_companies')->label('Has tax on companies')->default(false),
            TextInput::make('taxHomePermill')->numeric()->label('Home rate (permille)')->helperText('Permille value, e.g. 3.500 for 0.35%'),
            TextInput::make('taxCompanyPermill')->numeric()->label('Company rate (permille)')->helperText('Permille value, e.g. 3.500 for 0.35%'),
            TextInput::make('deduction')->numeric()->label('Deduction (NOK)'),
            TextInput::make('fortune_taxable_percent')->numeric()->label('Fortune taxable (%)')->helperText('Percentage of property value taxable for wealth tax, e.g. 70.00 for 70%'),
            Toggle::make('is_active')->default(true)->required(),
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
                TextColumn::make('fortune_taxable_percent')->label('Fortune %')->toggleable()->sortable()->alignment(Alignment::End)
                    ->formatStateUsing(fn ($state) => $state === null ? '' : number_format((float) $state, 2, ',', ' ').' %'),
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
                        return \App\Models\TaxProperty::query()
                            ->select('year')
                            ->distinct()
                            ->orderBy('year', 'desc')
                            ->pluck('year', 'year')
                            ->toArray();
                    }),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
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
