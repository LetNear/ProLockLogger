<?php

namespace App\Filament\Imports;

use App\Models\User;
use App\Models\UserInformation;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Illuminate\Support\Facades\Log as FacadesLog;
use Illuminate\Support\Facades\Session;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class UserImporter extends Importer
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
                ->rules(['required', 'email', 'max:255', 'unique:users,email']),
            ImportColumn::make('user_number')
                ->fillRecordUsing(function ($record, $state) {
                    return;
                })
                ->requiredMapping()
                ->rules(['required', 'max:20', 'unique:user_information,user_number']),
        ];
    }

    public function resolveRecord(): ?User
    {
        FacadesLog::info('Importing user data:', $this->data);

        // Validate email format
        if (!$this->isValidEmail($this->data['email'])) {
            throw new RowImportFailedException("Invalid email");
        }

        // Check for existing email
        $user = User::where('email', $this->data['email'])->first();
        if ($user) {
            throw new RowImportFailedException("Duplicate email");
        }

        // Check for duplicate user_number
        $userInfo = UserInformation::where('user_number', $this->data['user_number'])->first();
        if ($userInfo) {
            throw new RowImportFailedException("Duplicate user number");
        }

        $user = User::create([
            'name' => $this->data['name'],
            'email' => $this->data['email'],
            'role_number' => 2,
        ]);

        $roleName = $this->getRoleNameByNumber($user->role_number);
        if ($roleName) {
            $user->syncRoles($roleName);
        }

        UserInformation::updateOrCreate(
            ['user_id' => $user->id],
            ['user_number' => $this->data['user_number']]
        );

        return $user;
    }

    protected function isValidEmail(string $email): bool
    {
        return str_ends_with($email, '@my.cspc.edu.ph');
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
        $body = 'Your user import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
