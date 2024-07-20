<?php

namespace App\Filament\Resources\LabCapacityResource\Pages;

use App\Filament\Resources\LabCapacityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLabCapacities extends ListRecords
{
    protected static string $resource = LabCapacityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
