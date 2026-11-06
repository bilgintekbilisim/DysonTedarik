<?php

namespace App\Filament\Widgets;

use App\Models\ContactMessage;
use App\Models\QuoteRequest;
use Filament\Widgets\ChartWidget;

/** Son 30 günde gelen teklif talepleri ve iletişim mesajları (günlük). */
class InquiriesChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Son 30 gün: gelen talepler';

    protected ?string $maxHeight = '260px';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', QuoteRequest::class) ?? false;
    }

    protected function getData(): array
    {
        $days = collect(range(29, 0))->map(fn (int $d) => now()->subDays($d)->startOfDay());
        $count = function (string $model) use ($days) {
            $rows = $model::query()->where('created_at', '>=', $days->first())
                ->get(['created_at'])
                ->countBy(fn ($r) => $r->created_at->format('Y-m-d'));

            return $days->map(fn ($day) => $rows[$day->format('Y-m-d')] ?? 0)->all();
        };

        return [
            'datasets' => [
                ['label' => 'Teklif talepleri', 'data' => $count(QuoteRequest::class), 'borderColor' => '#f97316', 'backgroundColor' => 'rgba(249,115,22,.15)', 'fill' => true, 'tension' => 0.3],
                ['label' => 'İletişim mesajları', 'data' => $count(ContactMessage::class), 'borderColor' => '#2e4884', 'backgroundColor' => 'rgba(46,72,132,.10)', 'fill' => true, 'tension' => 0.3],
            ],
            'labels' => $days->map(fn ($d) => $d->format('d.m'))->all(),
        ];
    }

    protected function getOptions(): array
    {
        return ['scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]]];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
