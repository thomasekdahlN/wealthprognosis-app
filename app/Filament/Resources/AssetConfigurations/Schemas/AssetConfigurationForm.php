<?php

namespace App\Filament\Resources\AssetConfigurations\Schemas;

use App\Models\AssetConfiguration;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
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
            TextInput::make('expected_death_age')->numeric()->label('Expected Death Age')->minValue(0)->maxValue(125),
            TextInput::make('export_start_year')->numeric()->label('Export Start Year')->minValue(1925)->maxValue(2125),
            Toggle::make('public')->label('Public')->default(false),
            \App\Filament\Components\IconPicker::make('icon')->label('Icon'),
            FileUpload::make('image')
                ->label('Image')
                ->image()
                ->nullable()
                ->validationAttribute('image'),
            Select::make('color')
                ->label('Color')
                ->options([
                    '#3b82f6' => '<span style="display: inline-flex; align-items: center; gap: 0.5rem;"><span style="display: inline-block; width: 1rem; height: 1rem; border-radius: 0.25rem; background-color: #3b82f6; border: 1px solid #e5e7eb;"></span>Blue</span>',
                    '#10b981' => '<span style="display: inline-flex; align-items: center; gap: 0.5rem;"><span style="display: inline-block; width: 1rem; height: 1rem; border-radius: 0.25rem; background-color: #10b981; border: 1px solid #e5e7eb;"></span>Green</span>',
                    '#f59e0b' => '<span style="display: inline-flex; align-items: center; gap: 0.5rem;"><span style="display: inline-block; width: 1rem; height: 1rem; border-radius: 0.25rem; background-color: #f59e0b; border: 1px solid #e5e7eb;"></span>Amber</span>',
                    '#ef4444' => '<span style="display: inline-flex; align-items: center; gap: 0.5rem;"><span style="display: inline-block; width: 1rem; height: 1rem; border-radius: 0.25rem; background-color: #ef4444; border: 1px solid #e5e7eb;"></span>Red</span>',
                    '#8b5cf6' => '<span style="display: inline-flex; align-items: center; gap: 0.5rem;"><span style="display: inline-block; width: 1rem; height: 1rem; border-radius: 0.25rem; background-color: #8b5cf6; border: 1px solid #e5e7eb;"></span>Purple</span>',
                    '#6b7280' => '<span style="display: inline-flex; align-items: center; gap: 0.5rem;"><span style="display: inline-block; width: 1rem; height: 1rem; border-radius: 0.25rem; background-color: #6b7280; border: 1px solid #e5e7eb;"></span>Gray</span>',
                ])
                ->allowHtml()
                ->default('#3b82f6')
                ->required()
                ->native(false)
                ->suffixIcon('heroicon-o-swatch'),
            TagsInput::make('tags')
                ->label('Tags')
                ->nullable()
                ->helperText('Add tags to categorize this configuration')
                ->separator(',')
                ->splitKeys(['Tab', 'Enter', ','])
                ->placeholder('Type and press Enter or comma to add tags'),
            Select::make('risk_tolerance')
                ->label('Risk Tolerance')
                ->options(AssetConfiguration::RISK_TOLERANCE_LEVELS)
                ->default('moderate')
                ->required()
                ->helperText('Select your financial risk tolerance level for investment decisions'),
        ]);
    }
}
