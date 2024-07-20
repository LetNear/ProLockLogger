<?php

namespace App\Filament\Resources\YearAndProgramResource\Pages;

use App\Filament\Resources\YearAndProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListYearAndPrograms extends ListRecords
{
    protected static string $resource = YearAndProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
