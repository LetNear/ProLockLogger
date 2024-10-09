<?php

namespace App\Exports;

use App\Models\RecentLogs;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LogBookExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * Fetch all recent logs for export where the role_id is 3.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Fetch records where role_id is 3, and use eager loading for related data
        return RecentLogs::with(['block', 'role', 'seat', 'userInformation', 'yearAndSemester'])
            ->where('role_id', 3)  // Filter where role_id is 3
            ->get();
    }

    /**
     * Define the column headings.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'User Number',
            'User Name',
           
            'Block',
            'Date',               // Add the Date column
            'Year',
            'Time In',
            'Time Out',
            'Seat',
            'Instructor',
            'Year and Semester',
        ];
    }

    /**
     * Map the data for each row.
     *
     * @param RecentLogs $log
     * @return array
     */
    public function map($log): array
    {
        return [
            $log->user_number ?? 'N/A',
            $log->user_name ?? 'N/A',
           
            $log->block->block ?? 'N/A',  // Assuming the block model has a 'block' field
            $log->created_at->format('Y-m-d'), // Extract only the date from 'created_at'
            $log->year ?? 'N/A',
            $log->time_in ?? 'N/A',
            $log->time_out ?? 'N/A',
            $log->seat->seat_number ?? 'N/A',  // Assuming seat model has a 'seat_number'
            $log->assigned_instructor ?? 'N/A',
            $log->yearAndSemester->school_year . ' ' . $log->yearAndSemester->semester ?? 'N/A',
        ];
    }
}
