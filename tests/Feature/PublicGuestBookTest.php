<?php

namespace Tests\Feature;

use App\Models\GuestBook;
use App\Models\Queue;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicGuestBookTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_guest_book_page_from_queue_ticket(): void
    {
        $service = Service::query()->create([
            'name' => 'Administrasi Kepegawaian',
            'code' => 'A',
            'description' => 'Layanan administrasi',
            'is_active' => true,
        ]);

        $queue = Queue::query()->create([
            'service_id' => $service->id,
            'ticket_number' => 'A-001',
            'queue_date' => now()->toDateString(),
            'status' => 'waiting',
            'queued_at' => now(),
        ]);

        $response = $this->get(route('public.guest-book.show', $queue));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/GuestBookFeedback')
                ->where('ticket.id', $queue->id)
                ->where('ticket.ticketNumber', 'A-001')
                ->has('guestBook')
            );
    }

    public function test_guest_book_form_upserts_data_for_same_queue(): void
    {
        $service = Service::query()->create([
            'name' => 'Administrasi Kepegawaian',
            'code' => 'A',
            'description' => 'Layanan administrasi',
            'is_active' => true,
        ]);

        $queue = Queue::query()->create([
            'service_id' => $service->id,
            'ticket_number' => 'A-002',
            'queue_date' => now()->toDateString(),
            'status' => 'serving',
            'queued_at' => now(),
        ]);

        $payload = [
            'guest_name' => 'Rudi',
            'institution' => 'BKPSDM',
            'phone_number' => '08123456789',
            'visit_purpose' => 'Konsultasi data',
            'rating' => 4,
            'feedback' => 'Pelayanan cepat.',
            'would_recommend' => true,
        ];

        $this->put(route('public.guest-book.upsert', $queue), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('guest_books', [
            'queue_id' => $queue->id,
            'guest_name' => 'Rudi',
            'rating' => 4,
        ]);

        $this->put(route('public.guest-book.upsert', $queue), [
            ...$payload,
            'guest_name' => 'Rudi Update',
            'rating' => 5,
        ])->assertRedirect();

        $this->assertSame(1, GuestBook::query()->where('queue_id', $queue->id)->count());
        $this->assertDatabaseHas('guest_books', [
            'queue_id' => $queue->id,
            'guest_name' => 'Rudi Update',
            'rating' => 5,
        ]);
    }
}
