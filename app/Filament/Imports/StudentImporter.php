<?php

namespace App\Filament\Imports;

use App\Models\User;
use App\Models\UserInformation;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Log as FacadesLog;
use Illuminate\Support\Facades\Session;

class StudentImporter extends Importer
{
    protected static ?string $model = User::class;
    protected array $duplicateEmails = [];
    protected array $duplicateUserNumbers = [];
    protected array $invalidEmails = [];

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('email')
                ->requiredMapping()
                ->rules(['required', 'email', 'max:255']),
            ImportColumn::make('userInformation.user_number')
                ->requiredMapping()
                ->rules(['required', 'max:20']),
            ImportColumn::make('userInformation.year')
                ->requiredMapping()
                ->rules(['required', 'max:20']),
            ImportColumn::make('userInformation.block')
                ->requiredMapping()
                ->rules(['required', 'max:20']),
        ];
    }

    public function resolveRecord(): ?User
    {
        FacadesLog::info('Importing student data:', $this->data);
    
        // Check if the email already exists
        if (User::where('email', $this->data['email'])->exists()) {
            $this->duplicateEmails[] = $this->data['email'];
            return null; // Skip processing for this user
        }
    
        // Check if the user number already exists
        if (UserInformation::where('user_number', $this->data['userInformation']['user_number'])->exists()) {
            $this->duplicateUserNumbers[] = $this->data['userInformation']['user_number'];
            return null; // Skip processing for this user
        }
    
        // Create the user
        $user = User::create([
            'name' => $this->data['name'],
            'email' => $this->data['email'],
            'role_number' => 3, // Set role_number for students
        ]);
    
        // Sync role based on role_number
        $roleName = $this->getRoleNameByNumber($user->role_number);
        if ($roleName) {
            $user->syncRoles($roleName);
        }
    
        // Log before updating or creating UserInformation
        FacadesLog::info('Creating or updating UserInformation for user ID:', ['user_id' => $user->id, 'user_number' => $this->data['userInformation']['user_number']]);
    
        // Update or create UserInformation including year
        UserInformation::updateOrCreate(
            ['user_id' => $user->id],
            [
                'user_number' => $this->data['userInformation']['user_number'],
                'year' => $this->data['userInformation']['year'],    // Add year
                // Block logic has been removed
            ]
        );
    
        return $user;
    }
    

    

    protected function getRoleNameByNumber(int $roleNumber): ?string
    {
        $roles = [
            1 => 'Administrator',
            2 => 'Faculty',
            3 => 'Student',
        ];

        return $roles[$roleNumber] ?? null;
    }

    public function afterImport()
    {
        if (!empty($this->duplicateEmails)) {
            Session::flash('duplicateEmails', $this->duplicateEmails);
        }

        if (!empty($this->duplicateUserNumbers)) {
            Session::flash('duplicateUserNumbers', $this->duplicateUserNumbers);
        }

        if (!empty($this->invalidEmails)) {
            Session::flash('invalidEmails', $this->invalidEmails);
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your student import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
