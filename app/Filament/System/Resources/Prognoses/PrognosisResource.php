<?php

namespace App\Filament\System\Resources\Prognoses;

use App\Filament\Components\IconPicker;
use App\Models\PrognosisType as Prognosis;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PrognosisResource extends Resource
{
    protected static ?string $model = Prognosis::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

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
            TextInput::make('code')->required()->unique(ignoreRecord: true),
            TextInput::make('label')->required(),
            IconPicker::make('icon')
                ->label('Icon'),
            Select::make('color')->options([
                'primary' => 'Primary',
                'success' => 'Success',
                'info' => 'Info',
                'warning' => 'Warning',
                'danger' => 'Danger',
                'gray' => 'Gray',
            ])->native(false),
            Textarea::make('description')->columnSpanFull(),
            Toggle::make('public')->inline(false)->label('Public')->default(true),
            Toggle::make('is_active')->inline(false)->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->sortable()->searchable(),
                TextColumn::make('label')
                    ->sortable()
                    ->searchable()
                    ->icon(fn ($record) => $record->icon)
                    ->color(fn ($record) => $record->color ?? 'gray'),
                TextColumn::make('description')->limit(80)->wrap(),
                IconColumn::make('public')->boolean()->label('Public'),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('updatedBy.name')->label('Last updated by')->badge()->color('gray')->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('updated_at')->dateTime()->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active')->placeholder('All'),
                SelectFilter::make('color')->options([
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

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'secondary';
    }
}
