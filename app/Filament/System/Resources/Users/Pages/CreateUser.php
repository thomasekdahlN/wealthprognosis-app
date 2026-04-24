<?php

namespace App\Filament\System\Resources\Users\Pages;

use App\Filament\System\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
