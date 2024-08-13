<?php 

namespace App\Filament\Imports;

use App\Models\User;
use App\Models\UserInformation;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Log as FacadesLog;

class StudentImporter extends Importer
{
    protected static ?string $model = User::class;

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
        ];
    }

    public function resolveRecord(): ?User
    {
        FacadesLog::info('Importing student data:', $this->data);

        // Update or create the User record
        $user = User::updateOrCreate(
            ['email' => $this->data['email']], // Ensure the key matches the CSV header
            [
                'name' => $this->data['name'], // Ensure the key matches the CSV header
                'role_number' => 3, // Set role_number for students
            ]
        );

        // Assign the "Student" role using Spatie Permission
        $roleName = $this->getRoleNameByNumber($user->role_number);
        if ($roleName) {
            $user->syncRoles($roleName);
        }

        // Update or create the UserInformation record
        UserInformation::updateOrCreate(
            ['user_id' => $user->id],
            [
                'user_number' => $this->data['user_number'], // Ensure key matches CSV
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

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your student import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
