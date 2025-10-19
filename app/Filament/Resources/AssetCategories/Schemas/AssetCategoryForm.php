<?php

namespace App\Filament\Resources\AssetCategories\Schemas;

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
                    ->default('#6b7280')
                    ->required()
                    ->native(false)
                    ->suffixIcon('heroicon-o-swatch'),

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
