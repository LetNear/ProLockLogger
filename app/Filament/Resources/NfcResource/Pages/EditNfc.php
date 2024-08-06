<?php

namespace App\Filament\Resources\NfcResource\Pages;

use App\Filament\Resources\NfcResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNfc extends EditRecord
{
    protected static string $resource = NfcResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
