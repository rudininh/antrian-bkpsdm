<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\Counter;
use App\Models\Queue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringController extends Controller
{
    public function index(): Response
    {
        $today = Carbon::today();

        $activeCalls = Call::query()
            ->with(['queue.service', 'counter'])
            ->whereDate('called_at', $today)
            ->latest('called_at')
            ->take(8)
            ->get();

        $waitingQueues = Queue::query()
            ->with('service')
            ->whereDate('queue_date', $today)
            ->where('status', 'waiting')
            ->orderBy('queued_at')
            ->take(12)
            ->get();

        return Inertia::render('Monitoring/Index', [
            'summary' => [
                'waiting' => Queue::query()->whereDate('queue_date', $today)->where('status', 'waiting')->count(),
                'called' => Queue::query()->whereDate('queue_date', $today)->where('status', 'called')->count(),
                'serving' => Queue::query()->whereDate('queue_date', $today)->where('status', 'serving')->count(),
                'completed' => Queue::query()->whereDate('queue_date', $today)->where('status', 'completed')->count(),
            ],
            'activeCalls' => $activeCalls->map(fn (Call $call) => [
                'id' => $call->id,
                'queue_id' => $call->queue_id,
                'ticket_number' => $call->queue?->ticket_number,
                'service_name' => $call->queue?->service?->name,
                'counter_name' => $call->counter?->name ?? 'Receptionist',
                'status' => $call->status,
                'called_at' => $call->called_at?->format('H:i:s'),
                'notes' => $call->notes,
            ])->values(),
            'waitingQueues' => $waitingQueues->map(fn (Queue $queue) => [
                'id' => $queue->id,
                'ticket_number' => $queue->ticket_number,
                'service_name' => $queue->service?->name,
                'queued_at' => $queue->queued_at?->format('H:i'),
            ])->values(),
            'meta' => [
                'title' => 'Panel Panggilan Receptionist',
                'description' => 'Kelola panggilan aktif dan antrian menunggu dari satu meja receptionist.',
                'dateLabel' => $today->translatedFormat('d F Y'),
            ],
        ]);
    }

    public function call(Queue $queue): RedirectResponse
    {
        $timestamp = now();
        $counter = $this->resolveReceptionCounter();

        $queue->update([
            'counter_id' => $counter->id,
            'status' => 'called',
            'called_at' => $timestamp,
        ]);

        Call::query()->create([
            'queue_id' => $queue->id,
            'counter_id' => $counter->id,
            'status' => 'called',
            'called_at' => $timestamp,
        ]);

        return back()->with('success', 'Nomor antrian berhasil dipanggil oleh receptionist.');
    }

    public function start(Queue $queue): RedirectResponse
    {
        $queue->update([
            'status' => 'serving',
            'started_at' => now(),
        ]);

        $queue->calls()->latest()->first()?->update([
            'status' => 'serving',
            'started_at' => now(),
        ]);

        return back()->with('success', 'Layanan mulai diproses.');
    }

    public function complete(Queue $queue): RedirectResponse
    {
        $queue->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $queue->calls()->latest()->first()?->update([
            'status' => 'completed',
            'finished_at' => now(),
        ]);

        return back()->with('success', 'Layanan selesai.');
    }

    public function skip(Queue $queue): RedirectResponse
    {
        $queue->update([
            'status' => 'skipped',
        ]);

        $queue->calls()->latest()->first()?->update([
            'status' => 'skipped',
            'finished_at' => now(),
        ]);

        return back()->with('success', 'Antrian ditandai terlewati.');
    }

    protected function resolveReceptionCounter(): Counter
    {
        return Counter::query()->firstOrCreate(
            ['code' => 'RCP'],
            [
                'name' => 'Receptionist',
                'location' => 'Meja Receptionist',
                'is_active' => true,
            ],
        );
    }
}
