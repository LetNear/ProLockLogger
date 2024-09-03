<?php

namespace App\Filament\Imports;

use App\Models\Block;
use App\Models\User;
use App\Models\UserInformation;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Log as FacadesLog;
use Illuminate\Support\Facades\Session;

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

    public function resolveRecord(): ?User
    {
        FacadesLog::info('Importing student data:', $this->data);

        // Check if the email already exists
        if (User::where('email', $this->data['email'])->exists()) {
            return new RowImportFailedException('Email already exists');
        }

        // Check if the user number already exists
        if (UserInformation::where('user_number', ['user_number'])->exists()) {
            return new RowImportFailedException('User number already exists');
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

        // Find the block_id based on the block name
        $block = Block::where('block', $this->data['block'])->first();

        $blockId = $block ? $block->id : null;


        // Log before updating or creating UserInformation
        FacadesLog::info('Creating or updating UserInformation for user ID:', ['user_id' => $user->id, 'user_number' => ['user_number']]);

        // Update or create UserInformation including year
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
