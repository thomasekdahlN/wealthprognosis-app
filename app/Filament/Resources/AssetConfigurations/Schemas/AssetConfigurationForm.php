<?php

namespace App\Filament\Resources\AssetConfigurations\Schemas;

use App\Models\AssetConfiguration;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AssetConfigurationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Name')->required(),
            RichEditor::make('description')->label('Description')->columnSpanFull()->extraAttributes(['style' => 'min-height: calc(1.5rem * 8);']),
            TextInput::make('birth_year')->numeric()->label('Birth Year')->minValue(1925)->maxValue(2125),
            TextInput::make('prognose_age')->numeric()->label('Prognose Age')->minValue(0)->maxValue(125),
            TextInput::make('pension_official_age')->numeric()->label('Pension Official Age')->minValue(0)->maxValue(125),
            TextInput::make('pension_wish_age')->numeric()->label('Pension Wish Age')->minValue(0)->maxValue(125),
            TextInput::make('death_age')->numeric()->label('Death Age')->minValue(0)->maxValue(125),
            TextInput::make('export_start_year')->numeric()->label('Export Start Year')->minValue(1925)->maxValue(2125),
            Toggle::make('public')->label('Public')->default(false),
            TextInput::make('icon')->label('Icon'),
            FileUpload::make('image')->label('Image')->image(),
            TextInput::make('color')->label('Color'),
            TextInput::make('tags')->label('Tags (comma-separated)'),
            Select::make('risk_tolerance')
                ->label('Risk Tolerance')
                ->options(AssetConfiguration::RISK_TOLERANCE_LEVELS)
                ->default('moderate')
                ->required()
                ->helperText('Select your financial risk tolerance level for investment decisions'),
        ]);
    }
}
