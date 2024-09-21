<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

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

    // Correct method signature to apply filters to the table query
    protected function applyFiltersToTableQuery(Builder $query): Builder
    {
        // Apply the role number filter globally for role_number 1 and 2
        $query->whereIn('role_number', [1, 2]);
    
        // Apply other filters by calling the parent method
        return parent::applyFiltersToTableQuery($query);
    }
    
}
