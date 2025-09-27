<?php

namespace App\Filament\Resources\AssetYears\Pages;

use App\Filament\Resources\AssetYears\AssetYearResource;
use App\Models\Asset;
use App\Models\AssetConfiguration;
use App\Models\AssetYear;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListAssetYears extends ListRecords
{
    protected static string $resource = AssetYearResource::class;

    protected function getHeaderWidgets(): array
    {
        // Removed amounts-over-years chart widget per request
        return [];
    }

    public function getTitle(): string|Htmlable
    {
        $assetId = (int) (request()->route('asset') ?? request()->get('asset'));
        $asset = Asset::query()->find($assetId);
        if (! $asset) {
            return 'Asset Years';
        }

        $assetName = $asset->name ?? 'Asset #'.$asset->id;
        $assetType = $asset->getTypeLabel();

        return $assetName.' ('.$assetType.')';
    }

    protected function hasTable(): bool
    {
        return true;
    }

    protected function getHeaderActions(): array
    {
        $assetId = (int) (request()->route('asset') ?? request()->get('asset') ?? 0);
        $configurationId = (int) (request()->route('configuration') ?? request()->get('configuration') ?? request()->get('owner') ?? 0);
        $asset = $assetId ? Asset::query()->find($assetId) : null;

        return [
            Action::make('new_year')
                ->label('New year')
                ->icon('heroicon-m-plus')
                ->color('primary')
                ->action(function () use ($assetId, $configurationId) {
                    // Require explicit context from the page URL as requested
                    if ($assetId <= 0 || $configurationId <= 0) {
                        Notification::make()
                            ->title('Missing context')
                            ->body('This page requires both configuration and asset context.')
                            ->danger()
                            ->send();

                        return null;
                    }

                    // Determine next year for this asset
                    $maxYear = AssetYear::query()->where('asset_id', $assetId)->max('year');
                    $nextYear = $maxYear ? ((int) $maxYear + 1) : (int) date('Y');

                    // Create and associate
                    $asset = Asset::query()->find($assetId);
                    if (! $asset) {
                        Notification::make()
                            ->title('Asset not found')
                            ->body('Asset ID: '.$assetId)
                            ->danger()
                            ->send();

                        return null;
                    }

                    $record = new AssetYear;
                    $record->year = $nextYear;
                    $record->asset()->associate($asset);
                    $record->assetConfiguration()->associate($configurationId);
                    $record->save();

                    Notification::make()->title('Year '.$nextYear.' added')->success()->send();

                    // Refresh page to update the table immediately
                    $this->redirect(request()->fullUrl());

                    return null;
                }),
            Action::make('edit_asset')
                ->label('Edit Asset')
                ->icon('heroicon-m-pencil-square')
                ->color('primary')
                ->modalWidth('7xl')
                ->form([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->default($asset?->name),

                    \Filament\Forms\Components\RichEditor::make('description')
                        ->label('Description')
                        ->columnSpanFull()
                        ->extraAttributes(['style' => 'min-height: calc(1.5rem * 8);'])
                        ->default($asset?->description),

                    \Filament\Forms\Components\Select::make('asset_type')
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
                        ->required()
                        ->default($asset?->asset_type),

                    \Filament\Forms\Components\TextInput::make('asset_type_category')
                        ->label('Category')
                        ->prefixIcon('heroicon-o-tag')
                        ->default(function (\Filament\Schemas\Components\Utilities\Get $get): string {
                            $code = (string) ($get('asset_type') ?? '');
                            if ($code === '') {
                                return '';
                            }

                            return (string) (\App\Models\AssetType::query()->where('type', $code)->value('category') ?? '\u2014');
                        })
                        ->disabled()
                        ->dehydrated(false),

                    \Filament\Forms\Components\TextInput::make('asset_type_tax_type')
                        ->label('Tax Type')
                        ->prefixIcon('heroicon-o-receipt-percent')
                        ->default(function (\Filament\Schemas\Components\Utilities\Get $get): string {
                            $code = (string) ($get('asset_type') ?? '');
                            if ($code === '') {
                                return '';
                            }
                            $type = \App\Models\AssetType::with('taxType')->where('type', $code)->first();

                            return (string) ($type->taxType->name ?? '\u2014');
                        })
                        ->disabled()
                        ->dehydrated(false),

                    \Filament\Forms\Components\TextInput::make('asset_type_liquid')
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

                    \Filament\Forms\Components\TextInput::make('asset_can_generate_income')
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

                    \Filament\Forms\Components\TextInput::make('asset_can_generate_expenses')
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

                    \Filament\Forms\Components\TextInput::make('asset_can_have_mortgage')
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

                    \Filament\Forms\Components\TextInput::make('asset_can_have_market_value')
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

                    \Filament\Forms\Components\Select::make('group')
                        ->label('Group')
                        ->options(\App\Models\Asset::GROUPS)
                        ->default($asset?->group ?? 'private')
                        ->required()
                        ->helperText('Select whether this asset belongs to private or company portfolio'),

                    \Filament\Forms\Components\Select::make('tax_country')
                        ->label('Tax Country')
                        ->live()
                        ->options(function () {
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
                        })
                        ->default($asset?->tax_country ?? 'no')
                        ->searchable()
                        ->preload()
                        ->required(),

                    \Filament\Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default($asset?->is_active ?? true)
                        ->required(),
                ])
                ->action(function (array $data) use ($asset) {
                    if (! $asset) {
                        Notification::make()
                            ->title('Asset not found')
                            ->danger()
                            ->send();

                        return;
                    }

                    $asset->fill($data);
                    $asset->save();

                    Notification::make()
                        ->title('Asset updated')
                        ->success()
                        ->send();
                })
                ->modalSubmitActionLabel('Save changes')
                ->modalCancelActionLabel('Cancel'),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->label('Delete selected years')
                    ->requiresConfirmation(),
            ]),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $configurationId = request()->route('configuration') ?? request()->get('configuration') ?? request()->get('owner');
        $assetId = request()->route('asset') ?? request()->get('asset');

        $query = AssetYear::query();
        if ($configurationId) {
            $query->where('asset_configuration_id', $configurationId);
        }
        if ($assetId) {
            $query->where('asset_id', $assetId);
        }

        return $query;
    }

    public function getBreadcrumbs(): array
    {
        $configurationId = (int) (request()->route('configuration') ?? request()->get('configuration') ?? request()->get('owner'));
        $assetId = (int) (request()->route('asset') ?? request()->get('asset'));

        $crumbs = [];
        // Map is [url => label]
        $crumbs[\App\Filament\Resources\AssetConfigurations\AssetConfigurationResource::getUrl('index')] = __('Configurations');

        if ($configurationId) {
            $configuration = AssetConfiguration::find($configurationId);
            $crumbs[\App\Filament\Resources\AssetConfigurations\AssetConfigurationResource::getUrl('assets', ['record' => $configurationId])] = $configuration?->name ?? (__('Configuration').' #'.$configurationId);
        }

        if ($assetId) {
            $asset = Asset::find($assetId);
            $crumbs[] = $asset?->name ?? (__('Asset').' #'.$assetId); // final crumb as plain text
        }

        return $crumbs;
    }
}
