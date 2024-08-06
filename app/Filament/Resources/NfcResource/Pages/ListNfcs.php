<?php

namespace App\Filament\Resources\NfcResource\Pages;

use App\Filament\Resources\NfcResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNfcs extends ListRecords
{
    protected static string $resource = NfcResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
