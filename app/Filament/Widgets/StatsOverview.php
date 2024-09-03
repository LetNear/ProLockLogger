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

        // Calculating bounce rate (e.g., percentage of users with only one log entry)
        $totalSessions = RecentLogs::count();
        $singleEntryUsers = RecentLogs::select('user_number')
            ->groupBy('user_number')
            ->having(DB::raw('count(*)'), '=', 1)
            ->count();
        $bounceRate = $totalSessions > 0 ? round(($singleEntryUsers / $totalSessions) * 100, 2) . '%' : '0%';

        // Example of calculating average time on page (time difference between time_in and time_out)
        // Assuming time_in and time_out are stored as datetime
        $avgTimeOnPage = RecentLogs::select(DB::raw('AVG(TIMESTAMPDIFF(SECOND, time_in, time_out)) as avg_time'))
            ->value('avg_time');
        $avgTimeOnPageFormatted = $avgTimeOnPage ? gmdate('i:s', $avgTimeOnPage) : '0:00';

        return [
            Stat::make('Total Users', $totalUsers),
            Stat::make('Unique Views', $uniqueViews),
            Stat::make('Bounce Rate', $bounceRate),
            Stat::make('Average Time on Laboratory Usage', $avgTimeOnPageFormatted),
        ];
    }
}
