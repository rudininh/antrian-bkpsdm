<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
                    'queue_date' => $queue->queue_date?->toDateString(),
                    'status' => $queue->status,
                    'queued_at' => $queue->queued_at?->format('Y-m-d\TH:i'),
                    'queued_label' => $queue->queued_at?->format('d M Y H:i'),
                    'counter_name' => $queue->counter?->name,
                    'notes' => $queue->notes,
                ]),
            'services' => Service::query()
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
                'description' => 'Input dan kelola antrian harian dari meja receptionist.',
                'dateLabel' => $today->translatedFormat('d F Y'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateQueue($request);
        $service = Service::query()->findOrFail($data['service_id']);
        $queuedAt = Carbon::parse($data['queued_at']);
        $counter = $this->shouldAssignReceptionCounter($data['status'])
            ? $this->resolveReceptionCounter()
            : null;

        DB::transaction(function () use ($service, $counter, $data, $queuedAt) {
            Service::query()->whereKey($service->id)->lockForUpdate()->first();

            Queue::query()->create([
                'service_id' => $service->id,
                'counter_id' => $counter?->id,
                'ticket_number' => $data['ticket_number'] ?: $this->generateTicketNumber($service, $queuedAt),
                'queue_date' => $queuedAt->toDateString(),
                'status' => $data['status'],
                'queued_at' => $queuedAt,
                'called_at' => in_array($data['status'], ['called', 'serving', 'completed'], true) ? $queuedAt : null,
                'started_at' => in_array($data['status'], ['serving', 'completed'], true) ? $queuedAt : null,
                'completed_at' => $data['status'] === 'completed' ? $queuedAt : null,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return back()->with('success', 'Antrian berhasil ditambahkan.');
    }

    public function update(Request $request, Queue $queue): RedirectResponse
    {
        $data = $this->validateQueue($request, $queue);
        $service = Service::query()->findOrFail($data['service_id']);
        $queuedAt = Carbon::parse($data['queued_at']);
        $counter = $this->shouldAssignReceptionCounter($data['status'])
            ? $this->resolveReceptionCounter()
            : null;

        $queue->update([
            'service_id' => $service->id,
            'counter_id' => $counter?->id,
            'ticket_number' => $data['ticket_number'] ?: $queue->ticket_number,
            'queue_date' => $queuedAt->toDateString(),
            'status' => $data['status'],
            'queued_at' => $queuedAt,
            'called_at' => in_array($data['status'], ['called', 'serving', 'completed'], true) ? ($queue->called_at ?? $queuedAt) : null,
            'started_at' => in_array($data['status'], ['serving', 'completed'], true) ? ($queue->started_at ?? $queuedAt) : null,
            'completed_at' => $data['status'] === 'completed' ? ($queue->completed_at ?? $queuedAt) : null,
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
        $queuedAt = $request->input('queued_at');

        return $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'ticket_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('queues', 'ticket_number')
                    ->where(fn ($query) => $query->whereDate('queue_date', $queuedAt))
                    ->ignore($queue?->id),
            ],
            'status' => ['required', 'in:waiting,called,serving,completed,skipped,cancelled'],
            'queued_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    protected function generateTicketNumber(Service $service, Carbon $queuedAt): string
    {
        $maxSequence = Queue::query()
            ->where('service_id', $service->id)
            ->whereDate('queue_date', $queuedAt->toDateString())
            ->where('ticket_number', 'like', strtoupper($service->code).'-%')
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(ticket_number, '-', -1) AS UNSIGNED)) as max_sequence")
            ->value('max_sequence');

        $nextSequence = ((int) $maxSequence) + 1;

        return sprintf('%s-%03d', strtoupper($service->code), $nextSequence);
    }

    protected function shouldAssignReceptionCounter(string $status): bool
    {
        return in_array($status, ['called', 'serving', 'completed'], true);
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
