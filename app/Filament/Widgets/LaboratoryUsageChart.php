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
        $startDate = $this->startDate ? Carbon::parse($this->startDate) : Carbon::now()->subMonth()->startOfMonth();
        $endDate = $this->endDate ? Carbon::parse($this->endDate) : Carbon::now()->endOfMonth();

        $usageData = RecentLogs::selectRaw('DATE(time_in) as date, COUNT(*) as count')
            ->whereNotNull('time_in') // Ensure time_in is not null
            ->whereBetween('time_in', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->all();

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
        return 'line';
    }

    public function updateChart(): void
    {
        $this->emitSelf('updateChart');
    }
}
