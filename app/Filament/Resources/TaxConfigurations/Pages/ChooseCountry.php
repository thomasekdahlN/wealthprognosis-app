<?php

namespace App\Filament\Resources\TaxConfigurations\Pages;

use App\Filament\Resources\TaxConfigurations\TaxConfigurationResource;
use App\Models\TaxConfiguration;
use BackedEnum;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ChooseCountry extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.resources.tax-configurations.pages.choose-country';

    protected static ?string $title = 'Choose Country';

    protected static ?string $navigationLabel = 'Tax Configurations';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-globe-europe-africa';

    protected static ?int $navigationSort = 4;

    protected static \UnitEnum|string|null $navigationGroup = 'Setup';

    protected static string $resource = TaxConfigurationResource::class;

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
                TextColumn::make('name')->label('Country')->searchable(),
                TextColumn::make('code')
                    ->label('Country (Code)')
                    ->formatStateUsing(fn ($state, array $record) => sprintf('%s (%s)', (string) ($record['name'] ?? strtoupper((string) $state)), strtoupper((string) $state)))
                    ->badge()
                    ->searchable(),
            ])
            ->records(fn () => $this->getTableRecords())
            ->paginated(false)
            ->recordUrl(fn (array $record) => TaxConfigurationResource::getUrl('choose-year', ['country' => $record['code']]))
            ->emptyStateHeading('No supported countries configured');
    }

    public function getTableRecordKey(Model|array $record): string
    {
        if (is_array($record)) {
            return (string) ($record['key'] ?? $record['code'] ?? sha1(json_encode($record)));
        }

        return (string) $record->getKey();
    }

    public function getTableRecords(): Collection
    {
        // Prefer existing data: list distinct country codes from TaxConfiguration, mapped to labels via config
        $codes = TaxConfiguration::query()->distinct()->pluck('country_code');
        $map = collect(config('app.supported_countries', []));
        $records = $codes->map(function ($code) use ($map) {
            $code = (string) $code;
            $name = (string) ($map[$code] ?? strtoupper($code));

            return [
                'key' => $code,
                'code' => $code,
                'name' => $name,
            ];
        });

        // Fallback to Norway if no records yet
        if ($records->isEmpty()) {
            $records = collect([
                ['key' => 'no', 'code' => 'no', 'name' => 'Norway'],
            ]);
        }

        return $records->values();
    }

    public static function getResource(): string
    {
        return TaxConfigurationResource::class;
    }
}
