<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class QueueController extends Controller
{
    public function index(): Response
    {
        $today = Carbon::today();

        return Inertia::render('Queues/Index', [
            'queues' => Queue::query()
                ->with(['service', 'counter'])
                ->whereDate('queue_date', $today)
                ->latest('queued_at')
                ->get()
                ->map(fn (Queue $queue) => [
                    'id' => $queue->id,
                    'ticket_number' => $queue->ticket_number,
                    'service_id' => $queue->service_id,
                    'service_name' => $queue->service?->name,
                    'counter_id' => $queue->counter_id,
                    'counter_name' => $queue->counter?->name,
                    'queue_date' => $queue->queue_date?->toDateString(),
                    'status' => $queue->status,
                    'queued_at' => $queue->queued_at?->format('Y-m-d\TH:i'),
                    'queued_label' => $queue->queued_at?->format('d M Y H:i'),
                    'notes' => $queue->notes,
                ]),
            'services' => Service::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'counters' => Counter::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'statusOptions' => [
                ['value' => 'waiting', 'label' => 'Menunggu'],
                ['value' => 'called', 'label' => 'Dipanggil'],
                ['value' => 'serving', 'label' => 'Diproses'],
                ['value' => 'completed', 'label' => 'Selesai'],
                ['value' => 'skipped', 'label' => 'Terlewati'],
                ['value' => 'cancelled', 'label' => 'Batal'],
            ],
            'meta' => [
                'title' => 'Kelola Antrian',
                'description' => 'Input dan kelola antrian operasional harian.',
                'dateLabel' => $today->translatedFormat('d F Y'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateQueue($request);
        $service = Service::query()->findOrFail($data['service_id']);
        $queuedAt = Carbon::parse($data['queued_at']);

        Queue::query()->create([
            'service_id' => $service->id,
            'counter_id' => $data['counter_id'] ?? null,
            'ticket_number' => $data['ticket_number'] ?: $this->generateTicketNumber($service, $queuedAt),
            'queue_date' => $queuedAt->toDateString(),
            'status' => $data['status'],
            'queued_at' => $queuedAt,
            'called_at' => in_array($data['status'], ['called', 'serving', 'completed'], true) ? now() : null,
            'started_at' => in_array($data['status'], ['serving', 'completed'], true) ? now() : null,
            'completed_at' => $data['status'] === 'completed' ? now() : null,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Antrian berhasil ditambahkan.');
    }

    public function update(Request $request, Queue $queue): RedirectResponse
    {
        $data = $this->validateQueue($request, $queue);
        $service = Service::query()->findOrFail($data['service_id']);
        $queuedAt = Carbon::parse($data['queued_at']);

        $queue->update([
            'service_id' => $service->id,
            'counter_id' => $data['counter_id'] ?? null,
            'ticket_number' => $data['ticket_number'] ?: $queue->ticket_number,
            'queue_date' => $queuedAt->toDateString(),
            'status' => $data['status'],
            'queued_at' => $queuedAt,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Antrian berhasil diperbarui.');
    }

    public function destroy(Queue $queue): RedirectResponse
    {
        $queue->delete();

        return back()->with('success', 'Antrian berhasil dihapus.');
    }

    protected function validateQueue(Request $request, ?Queue $queue = null): array
    {
        return $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'counter_id' => ['nullable', 'exists:counters,id'],
            'ticket_number' => ['nullable', 'string', 'max:50', Rule::unique('queues', 'ticket_number')->ignore($queue?->id)],
            'status' => ['required', 'in:waiting,called,serving,completed,skipped,cancelled'],
            'queued_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
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
