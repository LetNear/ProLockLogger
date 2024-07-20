<?php

namespace App\Filament\Resources\RecentLogsResource\Pages;

use App\Filament\Resources\RecentLogsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecentLogs extends EditRecord
{
    protected static string $resource = RecentLogsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
