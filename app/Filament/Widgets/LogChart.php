<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\RecentLogs;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;

class LogChart extends ChartWidget
{
    protected static ?string $heading = 'User Activity';
    protected static ?int $sort = 2;
    
    // Default start and end dates
    public ?string $startDate = null;
    public ?string $endDate = null;

    // Define form schema to add date range inputs
    protected function getFormSchema(): array
    {
        return [
            DatePicker::make('startDate')
                ->label('Start Date')
                ->default(Carbon::now()->startOfMonth()->toDateString())
                ->reactive()
                ->afterStateUpdated(fn () => $this->updateChart()),
            
            DatePicker::make('endDate')
                ->label('End Date')
                ->default(Carbon::now()->endOfMonth()->toDateString())
                ->reactive()
                ->afterStateUpdated(fn () => $this->updateChart()),
        ];
    }

    // Fetch data with date range filter
    protected function getData(): array
    {
        // Set default date range if not set
        $startDate = $this->startDate ? Carbon::parse($this->startDate) : Carbon::now()->startOfMonth();
        $endDate = $this->endDate ? Carbon::parse($this->endDate) : Carbon::now()->endOfMonth();

        // Fetch logs grouped by role and filter by the selected date range
        $logs = RecentLogs::selectRaw('roles.name as role_name, COUNT(*) as count')
            ->join('roles', 'recent_logs.role_id', '=', 'roles.id')
            ->whereBetween('recent_logs.created_at', [$startDate, $endDate])
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
        return 'bar';
    }

    // Update the chart when date changes
    public function updateChart(): void
    {
        $this->emitSelf('updateChart');
    }
}
