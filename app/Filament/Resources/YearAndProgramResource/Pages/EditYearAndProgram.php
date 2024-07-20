<?php

namespace App\Filament\Resources\YearAndProgramResource\Pages;

use App\Filament\Resources\YearAndProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditYearAndProgram extends EditRecord
{
    protected static string $resource = YearAndProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
