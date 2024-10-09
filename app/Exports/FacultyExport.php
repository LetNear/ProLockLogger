<?php

namespace App\Exports;

use App\Models\RecentLogs;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FacultyExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * Fetch all recent logs for export where the role_id is 2 (Faculty).
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Fetch records where role_id is 2
        return RecentLogs::with(['block', 'role', 'seat', 'userInformation', 'yearAndSemester'])
            ->where('role_id', 2)  // Filter where role_id is 2 (Faculty)
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
            'User Name', 
            'Date',               // Add the Date column
            'Time In',
            'Time Out',
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
   
            $log->user_name ?? 'N/A',
            // Assuming the role model has a 'name' field
           // Assuming the block model has a 'block' field
            $log->created_at->format('Y-m-d'), // Extract only the date from 'created_at'
        
            $log->time_in ?? 'N/A',
            $log->time_out ?? 'N/A',
           
            $log->yearAndSemester->school_year . ' ' . $log->yearAndSemester->semester ?? 'N/A',
        ];
    }
}
