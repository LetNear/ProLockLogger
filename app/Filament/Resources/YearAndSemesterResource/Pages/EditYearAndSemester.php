<?php

namespace App\Filament\Resources\YearAndSemesterResource\Pages;

use App\Filament\Resources\YearAndSemesterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditYearAndSemester extends EditRecord
{
    protected static string $resource = YearAndSemesterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
