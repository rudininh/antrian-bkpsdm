<?php

namespace App\Http\Controllers;

use App\Models\GuestBook;
use App\Models\Queue;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PublicGuestBookController extends Controller
{
    public function kiosk(): Response
    {
        $today = Carbon::today();
        $activeQueue = Queue::query()
            ->with(['service', 'guestBook'])
            ->whereDate('queue_date', $today)
            ->whereIn('status', ['serving', 'called'])
            ->orderByRaw("case when status = 'serving' then 0 else 1 end")
            ->orderByDesc('called_at')
            ->first();

        return Inertia::render('Public/GuestBookKiosk', [
            'activeQueue' => $activeQueue ? [
                'id' => $activeQueue->id,
                'ticketNumber' => $activeQueue->ticket_number,
                'serviceName' => $activeQueue->service?->name,
                'status' => $activeQueue->status,
                'calledAt' => $activeQueue->called_at?->format('H:i'),
                'guestBook' => [
                    'guestName' => $activeQueue->guestBook?->guest_name ?? '',
                    'institution' => $activeQueue->guestBook?->institution ?? '',
                    'phoneNumber' => $activeQueue->guestBook?->phone_number ?? '',
                    'visitPurpose' => $activeQueue->guestBook?->visit_purpose ?? '',
                    'rating' => $activeQueue->guestBook?->rating,
                    'feedback' => $activeQueue->guestBook?->feedback ?? '',
                    'wouldRecommend' => $activeQueue->guestBook?->would_recommend,
                    'consultantName' => $activeQueue->guestBook?->consultant_name ?? '',
                ],
            ] : null,
            'options' => [
                'statuses' => [
                    'waiting' => 'Menunggu',
                    'called' => 'Dipanggil',
                    'serving' => 'Sedang Diproses',
                    'completed' => 'Selesai',
                    'skipped' => 'Terlewati',
                ],
            ],
            'meta' => [
                'title' => 'Buku Tamu',
                'description' => 'Silakan isi buku tamu dan feedback sesuai nomor antrian yang sedang dipanggil.',
            ],
        ]);
    }

    public function upsertFromKiosk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'queue_id' => ['required', 'integer', 'exists:queues,id'],
            'guest_name' => ['required', 'string', 'max:120'],
            'institution' => ['nullable', 'string', 'max:150'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'visit_purpose' => ['nullable', 'string', 'max:2000'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'feedback' => ['nullable', 'string', 'max:2000'],
            'would_recommend' => ['nullable', 'boolean'],
            'consultant_name' => ['nullable', 'string', 'max:150'],
        ]);

        $queue = Queue::query()->findOrFail($data['queue_id']);

        if (! in_array($queue->status, ['called', 'serving', 'completed'], true)) {
            return back()->with('success', 'Nomor antrian belum aktif untuk pengisian buku tamu.');
        }

        $existing = GuestBook::query()->where('queue_id', $queue->id)->first();

        GuestBook::query()->updateOrCreate(
            ['queue_id' => $queue->id],
            [
                'guest_name' => $data['guest_name'],
                'institution' => $data['institution'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
                'visit_purpose' => $data['visit_purpose'] ?? null,
                'rating' => $data['rating'] ?? null,
                'feedback' => $data['feedback'] ?? null,
                'would_recommend' => $data['would_recommend'] ?? null,
                'consultant_name' => $data['consultant_name'] ?? null,
                'submitted_at' => $existing?->submitted_at ?? now(),
            ],
        );

        return to_route('public.guest-book.kiosk')->with('success', 'Buku tamu berhasil disimpan.');
    }

    public function show(Queue $queue): Response
    {
        $queue->loadMissing(['service', 'guestBook']);

        return Inertia::render('Public/GuestBookFeedback', [
            'ticket' => [
                'id' => $queue->id,
                'ticketNumber' => $queue->ticket_number,
                'serviceName' => $queue->service?->name,
                'queueDate' => $queue->queue_date?->translatedFormat('d F Y'),
                'queuedAt' => $queue->queued_at?->format('H:i'),
                'status' => $queue->status,
            ],
            'guestBook' => [
                'guestName' => $queue->guestBook?->guest_name ?? '',
                'institution' => $queue->guestBook?->institution ?? '',
                'phoneNumber' => $queue->guestBook?->phone_number ?? '',
                'visitPurpose' => $queue->guestBook?->visit_purpose ?? '',
                'rating' => $queue->guestBook?->rating,
                'feedback' => $queue->guestBook?->feedback ?? '',
                'wouldRecommend' => $queue->guestBook?->would_recommend,
                'consultantName' => $queue->guestBook?->consultant_name ?? '',
                'submittedAt' => $queue->guestBook?->submitted_at?->format('d M Y H:i'),
            ],
            'options' => [
                'statuses' => [
                    'waiting' => 'Menunggu',
                    'called' => 'Dipanggil',
                    'serving' => 'Sedang Diproses',
                    'completed' => 'Selesai',
                    'skipped' => 'Terlewati',
                ],
            ],
        ]);
    }

    public function upsert(Request $request, Queue $queue): RedirectResponse
    {
        $data = $request->validate([
            'guest_name' => ['required', 'string', 'max:120'],
            'institution' => ['nullable', 'string', 'max:150'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'visit_purpose' => ['nullable', 'string', 'max:2000'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'feedback' => ['nullable', 'string', 'max:2000'],
            'would_recommend' => ['nullable', 'boolean'],
            'consultant_name' => ['nullable', 'string', 'max:150'],
        ]);

        $existing = GuestBook::query()->where('queue_id', $queue->id)->first();

        GuestBook::query()->updateOrCreate(
            ['queue_id' => $queue->id],
            [
                ...$data,
                'submitted_at' => $existing?->submitted_at ?? now(),
            ],
        );

        return back()->with('success', 'Buku tamu dan feedback berhasil disimpan.');
    }
}
