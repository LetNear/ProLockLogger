<?php

namespace App\Filament\Imports;

use App\Models\Computer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class ComputerImporter extends Importer
{
    protected static ?string $model = Computer::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('computer_number')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('brand')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('model')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('serial_number')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
        ];
    }

    public function resolveRecord(): ?Computer
    {
        try {
            // Check the total number of computers in the database
            $totalComputers = Computer::count();
            if ($totalComputers >= 40) {
                throw new RowImportFailedException('The maximum number of computers (40) has been reached.', 0);
            }
    
            // Validate the data before importing
            $validator = Validator::make($this->data, [
                'computer_number' => [
                    'required',
                    'integer',
                    'unique:computers,computer_number', // Ensure computer_number is unique
                ],
                'brand' => ['required', 'max:255'],
                'model' => ['required', 'max:255'],
                'serial_number' => [
                    'required',
                    'max:255',
                    'unique:computers,serial_number', // Ensure serial_number is unique
                ],
            ]);
    
            // If validation fails, it will throw a ValidationException
            $validator->validate();
    
            // If validation passes, return a new Computer model
            return new Computer($this->data);
    
        } catch (ValidationException $e) {
            // Convert validation errors to a JSON string and throw a RowImportFailedException
            $errors = json_encode($e->errors()); // Convert the error array to a JSON string
            throw new RowImportFailedException($errors, 0); // Pass 0 as the second argument (error code)
        }
    }
    
    

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your computer import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
