<?php

namespace App\Filament\Resources\YearAndSemesterResource\Pages;

use App\Filament\Resources\YearAndSemesterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListYearAndSemesters extends ListRecords
{
    protected static string $resource = YearAndSemesterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
