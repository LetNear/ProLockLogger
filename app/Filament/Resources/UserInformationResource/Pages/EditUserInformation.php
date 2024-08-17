<?php

namespace App\Filament\Resources\UserInformationResource\Pages;

use App\Filament\Resources\UserInformationResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserInformation extends EditRecord
{
    protected static string $resource = UserInformationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

  

    // public function mount($record): void
    // {
    //     parent::mount($record); // This loads the data into the form fields
    
    //     // Fetch the user based on user_id and get the role_number
    //     $user = User::find($this->record->user_id);
    
    //     // Automatically disable fields based on role_number if necessary
    //     if ($user) {
    //         $this->form->fill([
    //             'disableUserIdCard' => $user->role_number == 1 || $user->role_number == 2,
    //             'disableUserSeat' => $user->role_number == 1 || $user->role_number == 2,
    //         ]);
    //     }
    // }
}
