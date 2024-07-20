<?php

namespace App\Filament\Resources\LabCapacityResource\Pages;

use App\Filament\Resources\LabCapacityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLabCapacity extends EditRecord
{
    protected static string $resource = LabCapacityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
