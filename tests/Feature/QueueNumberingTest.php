<?php

namespace Tests\Feature;

use App\Models\Queue;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class QueueNumberingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_queue_number_can_repeat_on_a_new_day(): void
    {
        $service = Service::query()->create([
            'name' => 'Layanan Data dan Informasi',
            'code' => 'F',
            'description' => 'Layanan data',
            'is_active' => true,
        ]);

        Queue::query()->create([
            'service_id' => $service->id,
            'ticket_number' => 'F-001',
            'queue_date' => Carbon::today()->subDay()->toDateString(),
            'status' => 'waiting',
            'queued_at' => Carbon::today()->subDay()->setTime(9, 0),
        ]);

        $this->post(route('public.queue.store'), [
            'service_code' => 'F',
        ])->assertRedirect();

        $this->assertDatabaseHas('queues', [
            'service_id' => $service->id,
            'ticket_number' => 'F-001',
            'queue_date' => Carbon::today()->toDateString(),
        ]);
    }

    public function test_manual_queue_number_can_repeat_on_a_new_day(): void
    {
        $operator = User::factory()->create([
            'role' => 'operator',
        ]);

        $service = Service::query()->create([
            'name' => 'Administrasi Kepegawaian',
            'code' => 'A',
            'description' => 'Layanan administrasi',
            'is_active' => true,
        ]);

        Queue::query()->create([
            'service_id' => $service->id,
            'ticket_number' => 'A-001',
            'queue_date' => Carbon::today()->subDay()->toDateString(),
            'status' => 'waiting',
            'queued_at' => Carbon::today()->subDay()->setTime(8, 0),
        ]);

        $this->actingAs($operator)
            ->post(route('queues.store'), [
                'service_id' => $service->id,
                'ticket_number' => 'A-001',
                'status' => 'waiting',
                'queued_at' => Carbon::today()->setTime(9, 0)->format('Y-m-d H:i:s'),
                'notes' => null,
            ])
            ->assertRedirect();

        $this->assertSame(2, Queue::query()->count());
        $this->assertDatabaseHas('queues', [
            'service_id' => $service->id,
            'ticket_number' => 'A-001',
            'queue_date' => Carbon::today()->toDateString(),
        ]);
    }
}
