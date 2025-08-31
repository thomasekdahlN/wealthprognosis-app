<?php

namespace App\Filament\Resources\TaxConfigurations\Pages;

use App\Filament\Resources\TaxConfigurations\TaxConfigurationResource;
use App\Models\TaxConfiguration;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ChooseYear extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.resources.tax-configurations.pages.choose-year';

    protected static ?string $title = 'Choose Year';

    protected static string $resource = TaxConfigurationResource::class;

    public string $country;

    public function mount(string $country): void
    {
        $this->country = $country;
    }

    public function hasLogo(): bool
    {
        return false;
    }

    public function getMaxWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('year')->label('Year')->badge(),
            ])
            ->records(function () {
                return TaxConfiguration::query()
                    ->where('country_code', $this->country)
                    ->distinct()
                    ->orderBy('year')
                    ->pluck('year')
                    ->map(fn ($y) => ['key' => (string) $y, 'year' => (int) $y]);
            })
            ->paginated(false)
            ->recordUrl(fn (array $record) => TaxConfigurationResource::getUrl('list', [
                'country' => $this->country,
                'year' => $record['year'],
            ]))
            ->emptyStateHeading('No years available for this country');
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public static function getResource(): string
    {
        return TaxConfigurationResource::class;
    }
}
