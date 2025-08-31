<?php

namespace App\Filament\Resources\AiInstructions;

use App\Filament\Resources\AiInstructions\Pages\CreateAiInstruction;
use App\Filament\Resources\AiInstructions\Pages\EditAiInstruction;
use App\Filament\Resources\AiInstructions\Pages\ListAiInstructions;
use App\Filament\Resources\AiInstructions\Schemas\AiInstructionForm;
use App\Filament\Resources\AiInstructions\Tables\AiInstructionsTable;
use App\Models\AiInstruction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AiInstructionResource extends Resource
{
    protected static ?string $model = AiInstruction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static \UnitEnum|string|null $navigationGroup = 'Setup';

    protected static ?string $navigationLabel = 'AI Instructions';

    protected static ?int $navigationSort = 7;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $maxContentWidth = 'full';

    public static function form(Schema $schema): Schema
    {
        return AiInstructionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AiInstructionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiInstructions::route('/'),
            'create' => CreateAiInstruction::route('/create'),
            'edit' => EditAiInstruction::route('/{record}/edit'),
        ];
    }
}
