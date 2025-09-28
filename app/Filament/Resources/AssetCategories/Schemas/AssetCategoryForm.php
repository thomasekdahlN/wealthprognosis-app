<?php

namespace App\Filament\Resources\AssetCategories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AssetCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Category Code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50)
                    ->placeholder('e.g., investment_funds, securities')
                    ->helperText('Unique identifier for this category (use underscores, lowercase)'),

                TextInput::make('name')
                    ->label('Category Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., Investment Funds, Securities'),

                Textarea::make('description')
                    ->label('Description')
                    ->maxLength(1000)
                    ->rows(3)
                    ->placeholder('Detailed description of this asset category'),

                \App\Filament\Components\IconPicker::make('icon')
                    ->label('Icon'),

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
                        'slate' => 'Slate',
                        'zinc' => 'Zinc',
                        'neutral' => 'Neutral',
                        'stone' => 'Stone',
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

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Whether this category is currently in use'),
            ]);
    }
}
