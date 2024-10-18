<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\RecentLogs;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '5s';

    protected function getStats(): array
    {
        // Get the total number of users
        $totalUsers = User::count();

        // Example of calculating unique views (if using logs, for example)
        // Here assuming each user has a unique view per session (needs to be adapted based on real data)
        $uniqueViews = RecentLogs::distinct('user_number')->count();



        // Total number of logs
        $totalLogs = RecentLogs::count();

        // Count of logs where time_out is '00:00' (invalid logs)
        $invalidTimeOutLogs = RecentLogs::where('time_out', '=', '00:00')->count();

        // Calculate the valid logs percentage (the bounce rate based on valid logs)
        $validLogRate = $totalLogs > 0 ? round((($totalLogs - $invalidTimeOutLogs) / $totalLogs) * 100, 2) . '%' : '0%';

        //dd($validLogRate);  // Output the valid logs percentage






        // Example of calculating average time on page (time difference between time_in and time_out)
        // Assuming time_in and time_out are stored as datetime
        $avgTimeOnPage = DB::table('student_attendances')
            ->whereNotNull('time_in')
            ->whereNotNull('time_out')
            ->where('time_out', '!=', '00:00:00')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(SECOND, time_in, time_out)) as avg_time'))
            ->value('avg_time');

        // Format the time into hours:minutes:seconds
        $avgTimeOnPageFormatted = $avgTimeOnPage ? gmdate('H:i:s', (int) $avgTimeOnPage) : '0:00:00';

        //  dd($avgTimeOnPageFormatted);  // For debugging purposes to check the formatted output






        return [
            Stat::make('Total Users', $totalUsers),
            Stat::make('Users with Logs', $uniqueViews),
            Stat::make('Time-out Rate', $validLogRate),
            Stat::make('Average Time on Laboratory Usage', $avgTimeOnPageFormatted),

        ];
    }
}
