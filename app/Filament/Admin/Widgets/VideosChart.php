<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Video;

class VideosChart extends ChartWidget
{
    protected ?string $heading = 'Videos Chart';

    protected function getData(): array
    {
        // Group videos by date created
        $videos = Video::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        return [
            'datasets' => [
                [
                    'label' => 'Videos',
                    'data' => $videos->values(),
                ],
            ],
            'labels' => $videos->keys(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
