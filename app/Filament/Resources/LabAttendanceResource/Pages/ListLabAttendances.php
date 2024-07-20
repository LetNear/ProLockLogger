<?php

namespace App\Filament\Resources\LabAttendanceResource\Pages;

use App\Filament\Resources\LabAttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLabAttendances extends ListRecords
{
    protected static string $resource = LabAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
