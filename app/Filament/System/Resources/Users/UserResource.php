<?php

namespace App\Filament\System\Resources\Users;

use App\Filament\System\Resources\Users\Pages\CreateUser;
use App\Filament\System\Resources\Users\Pages\EditUser;
use App\Filament\System\Resources\Users\Pages\ListUsers;
use App\Filament\System\Resources\Users\Schemas\UserForm;
use App\Filament\System\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static UnitEnum|string|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Users';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $maxContentWidth = 'full';

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function getRecordUrl(string $name, array $parameters = []): string
    {
        return static::getUrl('edit', $parameters);
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
