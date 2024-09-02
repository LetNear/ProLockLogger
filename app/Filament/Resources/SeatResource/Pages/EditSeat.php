<?php

namespace App\Filament\Resources\SeatResource\Pages;

use App\Filament\Resources\SeatResource;
use App\Models\UserInformation;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSeat extends EditRecord
{
    protected static string $resource = SeatResource::class;

    protected function afterSave(): void
    {
        // After updating the seat, update the UserInformation record
        $seat = $this->record;
        $userInformation = UserInformation::find($seat->student_id);
        if ($userInformation) {
            $userInformation->seat_id = $seat->id;
            $userInformation->save();
        }
    }
}