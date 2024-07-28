<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;


class StatsOverview extends BaseWidget
{


    protected static ?string $pollingInterval = '5s';
    protected function getStats(): array
    {

        // Get the total number of users
        $totalUsers = User::count();

        // Get other statistics as needed
        $uniqueViews = '192.1k'; // Example static data
        $bounceRate = '21%'; // Example static data
        $avgTimeOnPage = '3:12'; // Example static data

        return [
            Stat::make('Total Users', $totalUsers),
            Stat::make('Unique views', $uniqueViews),
            Stat::make('Bounce rate', $bounceRate),
            Stat::make('Average time on page', $avgTimeOnPage),
        ];
    }
}
