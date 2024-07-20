<?php

namespace App\Filament\Resources\LabScheduleResource\Pages;

use App\Filament\Resources\LabScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLabSchedules extends ListRecords
{
    protected static string $resource = LabScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
