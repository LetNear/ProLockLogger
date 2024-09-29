<?php

namespace App\Filament\Resources\NfcResource\Pages;

use App\Filament\Resources\NfcResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Actions\ButtonAction;

class ListNfcs extends ListRecords
{
    protected static string $resource = NfcResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ButtonAction::make('Download NFC Installer')
                ->label('Download NFC Installer')
                ->url('https://github.com/jigabarda/NFCAppInstaller/releases/download/v1.0/NFCRegistrationApp.msi') // Custom URL to trigger NFC registration
                ->icon('heroicon-o-credit-card')
                ->color('primary'),
        ];
    }
}
