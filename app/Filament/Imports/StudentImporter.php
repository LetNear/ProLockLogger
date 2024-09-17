<?php

namespace App\Filament\Imports;

use App\Models\Block;
use App\Models\User;
use App\Models\UserInformation;
use App\Models\YearAndSemester; // Import the YearAndSemester model
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Log as FacadesLog;

class StudentImporter extends Importer
{
    protected static ?string $model = User::class;

    // Define required columns for StudentImporter
    protected array $requiredColumns = ['name', 'email', 'user_number', 'year', 'block'];

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('email')
                ->requiredMapping()
                ->rules(['required', 'email', 'max:255']),
            ImportColumn::make('user_number')
                ->fillRecordUsing(function ($record, $state) {
                    return;
                })
                ->label('User Number')
                ->requiredMapping()
                ->rules(['required', 'max:20']),
            ImportColumn::make('year')
                ->fillRecordUsing(function ($record, $state) {
                    return;
                })
                ->label('Year')
                ->requiredMapping()
                ->rules(['required', 'max:20']),
            ImportColumn::make('block')
                ->fillRecordUsing(function ($record, $state) {
                    return;
                })
                ->label('Block')
                ->requiredMapping()
                ->rules(['required', 'max:20']),
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
    }

    public function resolveRecord(): ?User
    {
        $this->validateColumns($this->data); // Validate before processing
        
        FacadesLog::info('Importing student data:', $this->data);
    
        // Validate email domain
        if (!str_ends_with($this->data['email'], '@my.cspc.edu.ph')) {
            throw new RowImportFailedException('Invalid email domain: Only emails ending with @my.cscp.edu.ph are allowed.');
        }
    
        // Validate year to be one of 1, 2, 3, 4
        if (!in_array($this->data['year'], ['1', '2', '3', '4'])) {
            throw new RowImportFailedException('Invalid year: Only 1, 2, 3, or 4 are allowed.');
        }
    
        // Validate block to be single letter A, B, C, etc.
        if (!preg_match('/^[A-Z]$/', $this->data['block'])) {
            throw new RowImportFailedException('Invalid block: Only single letters (A, B, C, etc.) are allowed.');
        }
    
        // Check if the email already exists
        if (User::where('email', $this->data['email'])->exists()) {
            throw new RowImportFailedException('Email already exists');
        }
    
        // Check if the user number already exists
        if (UserInformation::where('user_number', $this->data['user_number'])->exists()) {
            throw new RowImportFailedException('User number already exists');
        }
    
        // Fetch the active 'on-going' Year and Semester
        $onGoingYearAndSemester = YearAndSemester::where('status', 'on-going')->first();
    
        if (!$onGoingYearAndSemester) {
            throw new RowImportFailedException("No active (on-going) Year and Semester found. Please set one before importing students.");
        }
    
        // Create the user and associate with the active Year and Semester
        $user = User::create([
            'name' => $this->data['name'],
            'email' => $this->data['email'],
            'role_number' => 3, // Set role_number for students
            'year_and_semester_id' => $onGoingYearAndSemester->id,
        ]);
    
        // Sync role based on role_number
        $roleName = $this->getRoleNameByNumber($user->role_number);
        if ($roleName) {
            $user->syncRoles($roleName);
        }
    
        // Find the block_id based on the block name
        $block = Block::where('block', $this->data['block'])->first();
        $blockId = $block ? $block->id : null;
    
        // Log before updating or creating UserInformation
        FacadesLog::info('Creating or updating UserInformation for user ID:', ['user_id' => $user->id, 'user_number' => $this->data['user_number']]);
    
        // Update or create UserInformation including year and block
        UserInformation::updateOrCreate(
            ['user_id' => $user->id],
            [
                'user_number' => $this->data['user_number'],
                'year' => $this->data['year'],    // Add year
                'block_id' => $blockId,  // Add block
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
