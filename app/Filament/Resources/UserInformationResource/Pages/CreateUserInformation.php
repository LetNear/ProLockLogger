<?php

namespace App\Filament\Resources\UserInformationResource\Pages;

use App\Filament\Resources\UserInformationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUserInformation extends CreateRecord
{
    protected static string $resource = UserInformationResource::class;
}
