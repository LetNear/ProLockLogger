<?php

namespace App\Filament\Resources\FacultyAttResource\Pages;

use App\Filament\Resources\FacultyAttResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFacultyAtt extends EditRecord
{
    protected static string $resource = FacultyAttResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
}
