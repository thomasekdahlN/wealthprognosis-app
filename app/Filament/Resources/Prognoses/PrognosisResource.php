<?php

namespace App\Filament\Resources\Prognoses;

use App\Models\PrognosisType as Prognosis;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PrognosisResource extends Resource
{
    protected static ?string $model = Prognosis::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static \UnitEnum|string|null $navigationGroup = 'Setup';

    protected static ?string $navigationLabel = 'Prognosis Types';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'label';

    public static function shouldRegisterNavigation(): bool
    {
        return true; // Show in left navigation as requested
    }

    protected static ?string $maxContentWidth = 'full';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Forms\Components\TextInput::make('code')->required()->unique(ignoreRecord: true),
            \Filament\Forms\Components\TextInput::make('label')->required(),
            \Filament\Forms\Components\TextInput::make('icon')
                ->label('Icon (Heroicon name)')
                ->helperText('e.g. heroicon-o-check-badge, heroicon-o-arrow-trending-up')
                ->prefixIcon(fn ($get) => $get('icon') ?: 'heroicon-o-sparkles')
                ->maxLength(100),
            \Filament\Forms\Components\Select::make('color')->options([
                'primary' => 'Primary',
                'success' => 'Success',
                'info' => 'Info',
                'warning' => 'Warning',
                'danger' => 'Danger',
                'gray' => 'Gray',
            ])->native(false),
            \Filament\Forms\Components\Textarea::make('description')->columnSpanFull(),
            \Filament\Forms\Components\Toggle::make('public')->inline(false)->label('Public')->default(true),
            \Filament\Forms\Components\Toggle::make('is_active')->inline(false)->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('code')->sortable()->searchable(),
                \Filament\Tables\Columns\TextColumn::make('label')
                    ->sortable()
                    ->searchable()
                    ->icon(fn ($record) => $record->icon)
                    ->color(fn ($record) => $record->color ?? 'gray'),
                \Filament\Tables\Columns\TextColumn::make('description')->limit(80)->wrap(),
                \Filament\Tables\Columns\IconColumn::make('public')->boolean()->label('Public'),
                \Filament\Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
                \Filament\Tables\Columns\TextColumn::make('updatedBy.name')->label('Last updated by')->badge()->color('gray')->toggleable(isToggledHiddenByDefault: false),
                \Filament\Tables\Columns\TextColumn::make('updated_at')->dateTime()->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\TernaryFilter::make('is_active')->label('Active')->placeholder('All'),
                \Filament\Tables\Filters\SelectFilter::make('color')->options([
                    'primary' => 'Primary',
                    'success' => 'Success',
                    'info' => 'Info',
                    'warning' => 'Warning',
                    'danger' => 'Danger',
                    'gray' => 'Gray',
                ])->label('Color')->multiple()->preload(),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('No prognoses found')
            ->emptyStateDescription('Run the database seeder to create default prognosis types.')
            ->emptyStateIcon('heroicon-o-chart-bar');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrognoses::route('/'),
            'create' => Pages\CreatePrognosis::route('/create'),
            'edit' => Pages\EditPrognosis::route('/{record}/edit'),
        ];
    }
}
