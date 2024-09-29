<?php

namespace App\Filament\Imports;

use App\Models\User;
use App\Models\UserInformation;
use App\Models\YearAndSemester; // Import the YearAndSemester model
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Illuminate\Support\Facades\Log as FacadesLog;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class UserImporter extends Importer
{
    protected static ?string $model = User::class;

    // Define required columns for UserImporter
    protected array $requiredColumns = ['name', 'email', 'user_number', 'import_type'];

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('email')
                ->requiredMapping()
                ->rules(['required', 'email']),
            ImportColumn::make('user_number')
                ->fillRecordUsing(function ($record, $state) {
                    return;
                })
                ->requiredMapping()
                ->rules(['required', 'max:20']),
            ImportColumn::make('import_type')
                ->fillRecordUsing(function ($record, $state) {
                    return;
                })
                ->requiredMapping()
                ->rules(['required', 'in:instructor']), // Ensure the import_type is 'instructor' for UserImporter
        ];
    }

    // Method to validate columns
    public function validateColumns(array $data): void
    {
        $columns = array_keys($data);

        // Check if the incoming data columns match the required columns
        if (array_diff($this->requiredColumns, $columns)) {
            throw new RowImportFailedException("Invalid import data: expected columns are " . implode(', ', $this->requiredColumns));
        }

        // Check if the import type is correct
        if ($data['import_type'] !== 'instructor') {
            throw new RowImportFailedException("Invalid import type: expected 'instructor' for UserImporter.");
        }
    }

    public function resolveRecord(): ?User
    {
        $this->validateColumns($this->data); // Validate before processing
    
        FacadesLog::info('Importing user data:', $this->data);
    
        // Validate email format
        if (!$this->isValidEmail($this->data['email'])) {
            throw new RowImportFailedException("Invalid email format.");
        }
    
        // Fetch the active 'on-going' Year and Semester
        $onGoingYearAndSemester = YearAndSemester::where('status', 'on-going')->first();
    
        if (!$onGoingYearAndSemester) {
            throw new RowImportFailedException("No active (on-going) Year and Semester found. Please set one before importing users.");
        }
    
        // Check for existing email for the active year and semester
        $existingUserByEmail = User::where('email', $this->data['email'])
                    ->where('year_and_semester_id', $onGoingYearAndSemester->id)
                    ->first();
        
        if ($existingUserByEmail) {
            throw new RowImportFailedException("Duplicate email: The email already exists for the current Year and Semester.");
        }
    
        // Check for duplicate user_number for the active year and semester
        $existingUserByUserNumber = UserInformation::where('user_number', $this->data['user_number'])
                    ->whereHas('user', function ($query) use ($onGoingYearAndSemester) {
                        $query->where('year_and_semester_id', $onGoingYearAndSemester->id);
                    })
                    ->first();
        
        if ($existingUserByUserNumber) {
            throw new RowImportFailedException("Duplicate user number: The user number already exists for the current Year and Semester.");
        }
    
        // Create the user and associate with the active Year and Semester
        $user = User::create([
            'name' => $this->data['name'],
            'email' => $this->data['email'],
            'role_number' => 2, // Role number for instructors
            'year_and_semester_id' => $onGoingYearAndSemester->id,
        ]);
    
        // Assign role
        $roleName = $this->getRoleNameByNumber($user->role_number);
        if ($roleName) {
            $user->syncRoles($roleName);
        }
    
        // Update or create UserInformation with the user_number
        UserInformation::updateOrCreate(
            ['user_id' => $user->id],
            ['user_number' => $this->data['user_number']]
        );
    
        return $user;
    }
    

    protected function isValidEmail(string $email): bool
    {
        return str_ends_with($email, '@cspc.edu.ph');
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
