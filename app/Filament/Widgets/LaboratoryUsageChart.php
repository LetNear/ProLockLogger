<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\RecentLogs;
use Carbon\Carbon;

class LaboratoryUsageChart extends ChartWidget
{
    protected static ?string $heading = 'Laboratory Usage Over Time';
    protected static ?int $sort = 2;

    public ?string $startDate = null;
    public ?string $endDate = null;

    protected function getFormSchema(): array
    {
        return [
            \Filament\Forms\Components\DatePicker::make('startDate')
                ->label('Start Date')
                ->default(Carbon::now()->subMonth()->startOfMonth()->toDateString())
                ->reactive()
                ->afterStateUpdated(fn () => $this->updateChart()),

            \Filament\Forms\Components\DatePicker::make('endDate')
                ->label('End Date')
                ->default(Carbon::now()->endOfMonth()->toDateString())
                ->reactive()
                ->afterStateUpdated(fn () => $this->updateChart()),
        ];
    }

    protected function getData(): array
    {
        // Parse start and end dates or use default values
        $startDate = $this->startDate ? Carbon::parse($this->startDate) : Carbon::now()->subMonth()->startOfMonth();
        $endDate = $this->endDate ? Carbon::parse($this->endDate) : Carbon::now()->endOfMonth();

        // Adjust query to match time format
        $usageData = RecentLogs::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereNotNull('time_in')
            ->whereBetween('created_at', [$startDate, $endDate]) // Use created_at since it's full DATETIME
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->all();

        // Prepare data for the chart
        $labels = array_keys($usageData);
        $data = array_values($usageData);

        return [
            'datasets' => [
                [
                    'label' => 'Laboratory Usage',
                    'data' => $data,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1,
                    'lineTension' => 0.4, // Adds curvature to the line
                    'fill' => true, // Makes the area under the line filled
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    public function updateChart(): void
    {
        // Reload the chart data when the dates are updated
        $this->chartData($this->getData());
    }
}
