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
                Select::make('asset_configuration_id')
                    ->relationship('configuration', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('name')->required(),
                RichEditor::make('description')->columnSpanFull()->extraAttributes(['style' => 'min-height: calc(1.5rem * 8);']),
                Select::make('asset_type')
                    ->relationship('assetType', 'name', modifyQueryUsing: fn ($q) => $q->active()->ordered())
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('group')
                    ->label('Group')
                    ->options(Asset::GROUPS)
                    ->default('private')
                    ->required()
                    ->helperText('Select whether this asset belongs to private or company portfolio'),
                TextInput::make('tax_type'),
                TextInput::make('tax_property')->maxLength(50),
                TextInput::make('tax_country')->maxLength(5)->default('no'),
                Toggle::make('is_active')->default(true)->required(),
            ]);
    }
}
