<?php

namespace App\Filament\Resources\FacultyAttResource\Pages;

use App\Filament\Resources\FacultyAttResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFacultyAtts extends ListRecords
{
    protected static string $resource = FacultyAttResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
