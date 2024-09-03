<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\LaboratoryAttendances;
use App\Models\RecentLogs;
use Carbon\Carbon;

class LaboratoryUsageChart extends ChartWidget
{
    protected static ?string $heading = 'Laboratory Usage Over Time';
    protected static ?int $sort = 2;

    // Define date range properties
    public ?string $startDate = null;
    public ?string $endDate = null;

    // Define form schema to add date range inputs
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
        // Set default date range if not set
        $startDate = $this->startDate ? Carbon::parse($this->startDate) : Carbon::now()->subMonth()->startOfMonth();
        $endDate = $this->endDate ? Carbon::parse($this->endDate) : Carbon::now()->endOfMonth();

        // Fetch attendance records grouped by date within the selected date range
        $usageData = RecentLogs::selectRaw('DATE(time_in) as date, COUNT(*) as count')
            ->whereBetween('time_in', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->all();

        // Prepare labels and data for the chart
        $labels = array_keys($usageData);
        $data = array_values($usageData);

        return [
            'datasets' => [
                [
                    'label' => 'Laboratory Usage',
                    'data' => $data,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line'; // Line chart to show trends over time
    }

    // Update the chart when the date changes
    public function updateChart(): void
    {
        $this->emitSelf('updateChart');
    }
}
