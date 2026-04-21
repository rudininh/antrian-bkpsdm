<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\Queue;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class PublicQueueController extends Controller
{
    public function index(): Response
    {
        $today = Carbon::today();

        return Inertia::render('Public/TakeQueue', [
            'publicPage' => [
                'title' => 'Ambil Nomor Antrian',
                'subtitle' => 'Pilih layanan, ambil nomor secara mandiri, lalu pantau panggilan Anda dari layar monitor publik.',
            ],
            'services' => $this->servicesForPublic($today),
            'liveCalls' => $this->liveCalls($today),
            'summary' => $this->summary($today),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
        ]);

        $service = Service::query()
            ->whereKey($data['service_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $queuedAt = now();

        $queue = Queue::query()->create([
            'service_id' => $service->id,
            'ticket_number' => $this->generateTicketNumber($service, $queuedAt),
            'queue_date' => $queuedAt->toDateString(),
            'status' => 'waiting',
            'queued_at' => $queuedAt,
        ]);

        return to_route('public.queue.success', $queue);
    }

    public function success(Queue $queue): Response
    {
        $queue->loadMissing('service');

        $today = Carbon::parse($queue->queue_date ?? $queue->queued_at ?? now())->startOfDay();
        $queuesAhead = Queue::query()
            ->where('service_id', $queue->service_id)
            ->whereDate('queue_date', $queue->queue_date)
            ->where('queued_at', '<', $queue->queued_at)
            ->whereIn('status', ['waiting', 'called', 'serving'])
            ->count();

        return Inertia::render('Public/TicketSuccess', [
            'publicPage' => [
                'title' => 'Nomor Antrian Berhasil Dibuat',
                'subtitle' => 'Simpan nomor Anda, lalu pantau panggilan dari layar monitor publik agar tidak terlewat.',
            ],
            'ticket' => [
                'id' => $queue->id,
                'ticketNumber' => $queue->ticket_number,
                'serviceName' => $queue->service?->name,
                'serviceCode' => $queue->service?->code,
                'queuedAt' => $queue->queued_at?->format('H:i'),
                'queueDate' => $queue->queue_date?->translatedFormat('d F Y'),
                'status' => $queue->status,
                'queuesAhead' => $queuesAhead,
            ],
            'liveCalls' => $this->liveCalls($today),
            'summary' => $this->summary($today),
        ]);
    }

    public function monitor(): Response
    {
        $today = Carbon::today();

        return Inertia::render('Public/Monitor', [
            'publicPage' => [
                'title' => 'Monitor Panggilan Publik',
                'subtitle' => 'Pantau nomor yang sedang dipanggil receptionist, status layanan aktif, dan antrean berikutnya secara real-time.',
            ],
            'liveCalls' => $this->liveCalls($today),
            'recentlyCompleted' => Call::query()
                ->with(['queue.service', 'counter'])
                ->whereDate('called_at', $today)
                ->where('status', 'completed')
                ->latest('finished_at')
                ->take(8)
                ->get()
                ->map(fn (Call $call) => [
                    'id' => $call->id,
                    'ticketNumber' => $call->queue?->ticket_number,
                    'serviceName' => $call->queue?->service?->name,
                    'counterName' => $call->counter?->name ?? 'Meja Receptionist',
                    'finishedAt' => $call->finished_at?->format('H:i') ?? $call->called_at?->format('H:i'),
                ])
                ->values(),
            'waitingByService' => Service::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (Service $service) => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'code' => $service->code,
                    'waiting' => Queue::query()
                        ->where('service_id', $service->id)
                        ->whereDate('queue_date', $today)
                        ->whereIn('status', ['waiting', 'called', 'serving'])
                        ->count(),
                    'nextTicket' => Queue::query()
                        ->where('service_id', $service->id)
                        ->whereDate('queue_date', $today)
                        ->whereIn('status', ['waiting', 'called', 'serving'])
                        ->orderByRaw("CASE status WHEN 'serving' THEN 1 WHEN 'called' THEN 2 ELSE 3 END")
                        ->orderBy('queued_at')
                        ->value('ticket_number'),
                ])
                ->values(),
            'summary' => $this->summary($today),
        ]);
    }

    protected function servicesForPublic(Carbon $today)
    {
        return Service::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'code' => $service->code,
                'description' => $service->description,
                'waitingCount' => Queue::query()
                    ->where('service_id', $service->id)
                    ->whereDate('queue_date', $today)
                    ->whereIn('status', ['waiting', 'called', 'serving'])
                    ->count(),
                'calledCount' => Queue::query()
                    ->where('service_id', $service->id)
                    ->whereDate('queue_date', $today)
                    ->where('status', 'called')
                    ->count(),
            ])
            ->values();
    }

    protected function liveCalls(Carbon $today)
    {
        return Call::query()
            ->with(['queue.service', 'counter'])
            ->whereDate('called_at', $today)
            ->whereIn('status', ['called', 'serving'])
            ->latest('called_at')
            ->take(6)
            ->get()
                ->map(fn (Call $call) => [
                    'id' => $call->id,
                    'ticketNumber' => $call->queue?->ticket_number,
                    'serviceName' => $call->queue?->service?->name,
                    'counterName' => $call->counter?->name ?? 'Meja Receptionist',
                    'status' => $call->status,
                    'calledAt' => $call->called_at?->format('H:i'),
                ])
            ->values();
    }

    protected function summary(Carbon $today): array
    {
        return [
            'waiting' => Queue::query()->whereDate('queue_date', $today)->where('status', 'waiting')->count(),
            'called' => Queue::query()->whereDate('queue_date', $today)->where('status', 'called')->count(),
            'serving' => Queue::query()->whereDate('queue_date', $today)->where('status', 'serving')->count(),
            'completed' => Queue::query()->whereDate('queue_date', $today)->where('status', 'completed')->count(),
        ];
    }

    protected function generateTicketNumber(Service $service, Carbon $queuedAt): string
    {
        $count = Queue::query()
            ->where('service_id', $service->id)
            ->whereDate('queue_date', $queuedAt->toDateString())
            ->count() + 1;

        return sprintf('%s-%03d', strtoupper($service->code), $count);
    }
}
