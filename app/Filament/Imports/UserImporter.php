<?php

namespace App\Filament\Imports;

use App\Models\User;
use App\Models\UserInformation;
use Illuminate\Support\Facades\Log as FacadesLog;
use Illuminate\Support\Facades\Session;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class UserImporter extends Importer
{
    protected static ?string $model = User::class;
    protected array $duplicateEmails = [];
    protected array $invalidEmails = [];
    protected array $duplicateUserNumbers = [];

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
        FacadesLog::info('Importing user data:', $this->data);

        // Validate email format
        if (!$this->isValidEmail($this->data['email'])) {
            $this->invalidEmails[] = $this->data['email'];
            return null; // Skip processing for this user
        }

        // Check for existing email
        $user = User::where('email', $this->data['email'])->first();
        if ($user) {
            // Collect duplicate emails
            $this->duplicateEmails[] = $this->data['email'];
            return null; // Skip processing for this user
        }

        // Check for duplicate user_number
        $userInfo = UserInformation::where('user_number', $this->data['user_number'])->first();
        if ($userInfo) {
            // Collect duplicate user_numbers
            $this->duplicateUserNumbers[] = $this->data['user_number'];
            return null; // Skip processing for this user
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

    public function afterImport()
    {
        // Store errors in session for display
        if (!empty($this->duplicateEmails)) {
            Session::flash('duplicateEmails', $this->duplicateEmails);
        }

        if (!empty($this->invalidEmails)) {
            Session::flash('invalidEmails', $this->invalidEmails);
        }

        if (!empty($this->duplicateUserNumbers)) {
            Session::flash('duplicateUserNumbers', $this->duplicateUserNumbers);
        }
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
