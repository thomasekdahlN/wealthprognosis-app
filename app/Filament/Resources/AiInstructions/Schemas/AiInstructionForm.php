<?php

namespace App\Filament\Resources\AiInstructions\Schemas;

use App\Models\AiInstruction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AiInstructionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Instruction name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Asset Portfolio Analysis')
                            ->helperText('A descriptive name for this AI instruction set')
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                Textarea::make('description')
                                    ->label('Description')
                                    ->maxLength(500)
                                    ->rows(3)
                                    ->placeholder('Brief description of what this instruction does and its purpose')
                                    ->helperText('Optional description for reference'),

                                TextInput::make('type')
                                    ->label('Type')
                                    ->maxLength(100)
                                    ->placeholder('e.g., portfolio_analysis, risk_assessment')
                                    ->helperText('Category or type identifier for this instruction'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Enable/disable this instruction'),

                                TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Order in lists (lower numbers first)'),

                                Select::make('model')
                                    ->label('AI Model')
                                    ->options(AiInstruction::getAvailableModels())
                                    ->default('gpt-4')
                                    ->required()
                                    ->helperText('OpenAI model to use'),
                            ]),
                    ]),

                Section::make('AI Configuration')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('max_tokens')
                                    ->label('Max Tokens')
                                    ->numeric()
                                    ->default(2000)
                                    ->required()
                                    ->minValue(100)
                                    ->maxValue(4000)
                                    ->helperText('Maximum response length (100-4000)'),

                                TextInput::make('temperature')
                                    ->label('Temperature')
                                    ->numeric()
                                    ->default(0.7)
                                    ->required()
                                    ->step(0.1)
                                    ->minValue(0)
                                    ->maxValue(2)
                                    ->helperText('Creativity level (0.0-2.0, lower = more focused)'),
                            ]),
                    ]),

                Section::make('Prompts')
                    ->schema([
                        Textarea::make('system_prompt')
                            ->label('System Prompt')
                            ->required()
                            ->rows(6)
                            ->columnSpanFull()
                            ->placeholder('You are a financial advisor analyzing asset portfolios...')
                            ->helperText('Instructions that define the AI\'s role and behavior'),

                        Textarea::make('user_prompt_template')
                            ->label('User Prompt Template')
                            ->required()
                            ->rows(8)
                            ->columnSpanFull()
                            ->placeholder('Please analyze the following asset portfolio data: {json_data}')
                            ->helperText('Template for the user message. Use {json_data} placeholder for asset data'),
                    ]),
            ])
            ->columns(1);
    }
}
