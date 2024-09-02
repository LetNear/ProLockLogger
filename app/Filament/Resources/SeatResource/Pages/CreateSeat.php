<?php

namespace App\Filament\Resources\SeatResource\Pages;

use App\Filament\Resources\SeatResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use App\Models\UserInformation;

class CreateSeat extends CreateRecord
{
    protected static string $resource = SeatResource::class;

    protected function afterSave(): void
    {
        // After saving the seat, update the UserInformation record
        $seat = $this->record;
        $userInformation = UserInformation::find($seat->student_id);
        if ($userInformation) {
            $userInformation->seat_id = $seat->id;
            $userInformation->save();
        }
    }
}
