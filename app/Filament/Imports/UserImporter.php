<?php

namespace App\Filament\Imports;

use App\Models\User;
use App\Models\UserInformation;
use App\Models\Block;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Log as FacadesLog;

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
                ->rules(['required', 'email', 'max:255']),
            ImportColumn::make('userInformation.student_id')
                ->requiredMapping()
                ->rules(['required', 'max:20']),
               
            ImportColumn::make('userInformation.year')
                ->requiredMapping()
                ->rules(['required', 'max:4']),
                
            ImportColumn::make('userInformation.block')
                ->requiredMapping()
                ->rules(['required', 'max:1']),
                
            ImportColumn::make('userInformation.program')
                ->requiredMapping() 
                ->rules(['required', 'max:255']),
                
        ];
    }
    

    public function resolveRecord(): ?User
    {
        FacadesLog::info('Importing user data:', $this->data);

        // Update or create the User record
        $user = User::updateOrCreate(
            ['email' => $this->data['email']], // Ensure the key matches the CSV header
            [
                'name' => $this->data['name'], // Ensure the key matches the CSV header
            ]
        );

        // Fetch the block ID using the block name from the CSV
        $blockId = $this->getBlockId($this->data['block']);

        // Update or create the UserInformation record
        UserInformation::updateOrCreate(
            ['user_id' => $user->id],
            [
                'student_number' => $this->data['student_number'], // Ensure key matches CSV
                'year' => $this->data['year'], // Ensure key matches CSV
                'program' => $this->data['program'], // Ensure key matches CSV
                'block_id' => $blockId, // Use resolved block ID
            ]
        );

        return $user;
    }

    protected function getBlockId(string $blockName): ?int
    {
        // Use the block name from the CSV to find the Block ID.
        $block = Block::firstOrCreate(['block' => $blockName]);
        return $block->id;
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
