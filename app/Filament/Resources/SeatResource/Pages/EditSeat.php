<?php

namespace App\Filament\Resources\SeatResource\Pages;

use App\Filament\Resources\SeatResource;
use App\Models\UserInformation;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditSeat extends EditRecord
{
    protected static string $resource = SeatResource::class;

    protected function afterSave(): void
    {
        // Log to check if the function is called
        Log::info('afterSave called in EditSeat');

        $seat = $this->record;
        $userInformation = UserInformation::find($seat->student_id);
        if ($userInformation) {
            $userInformation->seat_id = $seat->id;
            $userInformation->save();

            // Log the successful save
            Log::info("Seat ID {$seat->id} assigned to UserInformation ID {$userInformation->id}");
        } else {
            // Log the failure to find UserInformation
            Log::warning("UserInformation not found for student_id: {$seat->student_id}");
        }
    }
}