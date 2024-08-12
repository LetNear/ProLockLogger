<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public static function emitRefreshEvent()
    {
        static::getResource()::emit('refreshTable');
    }

    protected function getListeners(): array
    {
        return array_merge(
            parent::getListeners(),
            ['refreshTable' => '$refresh']
        );
    }
}
