<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\User;

class MembersChart extends ChartWidget
{
    protected ?string $heading = 'Members Chart';

    protected function getData(): array
    {
        $users = User::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        return [
            'datasets' => [
                [
                    'label' => 'Members',
                    'data' => $users->values(),
                ],
            ],
            'labels' => $users->keys(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
