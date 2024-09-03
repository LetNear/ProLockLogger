<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\RecentLogs;

class LogChart extends ChartWidget
{
    protected static ?string $heading = 'User Activity';

    protected function getData(): array
    {
        // Fetch the recent logs grouped by role and count the occurrences
        $logs = RecentLogs::selectRaw('roles.name as role_name, COUNT(*) as count')
            ->join('roles', 'recent_logs.role_id', '=', 'roles.id')
            ->groupBy('roles.name')
            ->pluck('count', 'role_name')
            ->all();

        return [
            'datasets' => [
                [
                    'label' => 'Number of Logs by Role',
                    'data' => array_values($logs),
                ],
            ],
            'labels' => array_keys($logs),
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // Changed to 'bar' for a better representation of roles
    }
}
