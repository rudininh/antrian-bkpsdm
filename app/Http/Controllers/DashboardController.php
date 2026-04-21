<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Models\Queue;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $today = Carbon::today();

        $todayQueues = Queue::query()
            ->with(['service', 'counter'])
            ->whereDate('queue_date', $today)
            ->latest('queued_at')
            ->get();

        $completedQueues = $todayQueues->where('status', 'completed');
        $activeCounters = Counter::query()->where('is_active', true)->count();

        $averageWait = (int) round(
            $completedQueues
                ->filter(fn (Queue $queue) => $queue->called_at && $queue->queued_at)
                ->avg(fn (Queue $queue) => $queue->queued_at->diffInMinutes($queue->called_at)) ?? 0
        );

        $stats = [
            [
                'label' => 'Antrian Hari Ini',
                'value' => $todayQueues->count(),
                'change' => $todayQueues->whereIn('status', ['waiting', 'called', 'serving'])->count().' aktif',
            ],
            [
                'label' => 'Loket Aktif',
                'value' => $activeCounters,
                'change' => Counter::query()->where('is_active', false)->count().' nonaktif',
            ],
            [
                'label' => 'Rata-rata Tunggu',
                'value' => str_pad((string) floor($averageWait / 60), 2, '0', STR_PAD_LEFT).':'.str_pad((string) ($averageWait % 60), 2, '0', STR_PAD_LEFT),
                'change' => 'menit hingga dipanggil',
            ],
            [
                'label' => 'Layanan Selesai',
                'value' => $completedQueues->count(),
                'change' => $todayQueues->where('status', 'serving')->count().' sedang diproses',
            ],
        ];

        $queues = $todayQueues
            ->take(8)
            ->map(fn (Queue $queue) => [
                'ticket' => $queue->ticket_number,
                'service' => $queue->service?->name,
                'counter' => $queue->counter?->name ?? 'Belum ditetapkan',
                'status' => $this->mapStatus($queue->status),
                'queued_at' => $queue->queued_at?->format('H:i'),
            ])
            ->values();

        $serviceBreakdown = $todayQueues
            ->groupBy(fn (Queue $queue) => $queue->service?->name ?? 'Lainnya')
            ->map(fn ($group, $service) => [
                'service' => $service,
                'total' => $group->count(),
            ])
            ->values();

        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
            'queues' => $queues,
            'serviceBreakdown' => $serviceBreakdown,
            'meta' => [
                'title' => 'Dashboard Operasional',
                'description' => 'Pantau arus layanan, status antrian, dan kinerja loket hari ini.',
                'dateLabel' => $today->translatedFormat('d F Y'),
            ],
        ]);
    }

    protected function mapStatus(string $status): string
    {
        return match ($status) {
            'waiting' => 'Menunggu',
            'called' => 'Dipanggil',
            'serving' => 'Diproses',
            'completed' => 'Selesai',
            'skipped' => 'Terlewati',
            'cancelled' => 'Batal',
            default => ucfirst($status),
        };
    }
}
