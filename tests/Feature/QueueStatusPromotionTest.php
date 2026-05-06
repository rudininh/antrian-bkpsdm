<?php

namespace Tests\Feature;

use App\Models\Call;
use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueStatusPromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_index_promotes_stale_called_queue_to_proses(): void
    {
        Carbon::setTestNow($now = Carbon::parse('2026-05-06 10:00:00'));
        try {
            $operator = User::factory()->create([
                'role' => 'operator',
            ]);

            $queue = $this->createStaleCalledQueue($now);

            $this->actingAs($operator)
                ->get(route('monitoring.index'))
                ->assertOk();

            $this->assertDatabaseHas('queues', [
                'id' => $queue->id,
                'status' => 'serving',
            ]);

            $this->assertDatabaseHas('calls', [
                'queue_id' => $queue->id,
                'status' => 'serving',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_public_queue_index_promotes_stale_called_queue_to_proses(): void
    {
        Carbon::setTestNow($now = Carbon::parse('2026-05-06 10:00:00'));
        try {
            $queue = $this->createStaleCalledQueue($now);

            $this->get(route('public.queue.index'))
                ->assertOk();

            $this->assertDatabaseHas('queues', [
                'id' => $queue->id,
                'status' => 'serving',
            ]);

            $this->assertDatabaseHas('calls', [
                'queue_id' => $queue->id,
                'status' => 'serving',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_monitoring_index_completes_stale_serving_queue(): void
    {
        Carbon::setTestNow($now = Carbon::parse('2026-05-06 10:00:00'));
        try {
            $operator = User::factory()->create([
                'role' => 'operator',
            ]);

            $queue = $this->createStaleServingQueue($now);

            $this->actingAs($operator)
                ->get(route('monitoring.index'))
                ->assertOk();

            $this->assertDatabaseHas('queues', [
                'id' => $queue->id,
                'status' => 'completed',
            ]);

            $this->assertDatabaseHas('calls', [
                'queue_id' => $queue->id,
                'status' => 'completed',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    protected function createStaleCalledQueue(Carbon $now): Queue
    {
        $service = Service::query()->create([
            'name' => 'Administrasi Kepegawaian',
            'code' => 'A',
            'description' => 'Kenaikan pangkat dan verifikasi data.',
            'is_active' => true,
        ]);

        $counter = Counter::query()->create([
            'name' => 'Receptionist',
            'code' => 'RCP',
            'location' => 'Meja Receptionist',
            'is_active' => true,
        ]);

        $queue = Queue::query()->create([
            'service_id' => $service->id,
            'counter_id' => $counter->id,
            'ticket_number' => 'A-001',
            'queue_date' => $now->toDateString(),
            'status' => 'called',
            'queued_at' => $now->copy()->subMinutes(5),
            'called_at' => $now->copy()->subMinutes(2),
        ]);

        Call::query()->create([
            'queue_id' => $queue->id,
            'counter_id' => $counter->id,
            'status' => 'called',
            'called_at' => $now->copy()->subMinutes(2),
        ]);

        return $queue;
    }

    protected function createStaleServingQueue(Carbon $now): Queue
    {
        $service = Service::query()->create([
            'name' => 'Administrasi Kepegawaian',
            'code' => 'A',
            'description' => 'Kenaikan pangkat dan verifikasi data.',
            'is_active' => true,
        ]);

        $counter = Counter::query()->create([
            'name' => 'Receptionist',
            'code' => 'RCP',
            'location' => 'Meja Receptionist',
            'is_active' => true,
        ]);

        $queue = Queue::query()->create([
            'service_id' => $service->id,
            'counter_id' => $counter->id,
            'ticket_number' => 'A-002',
            'queue_date' => $now->toDateString(),
            'status' => 'serving',
            'queued_at' => $now->copy()->subMinutes(8),
            'called_at' => $now->copy()->subMinutes(4),
            'started_at' => $now->copy()->subMinutes(4),
        ]);

        Call::query()->create([
            'queue_id' => $queue->id,
            'counter_id' => $counter->id,
            'status' => 'serving',
            'called_at' => $now->copy()->subMinutes(4),
            'started_at' => $now->copy()->subMinutes(4),
        ]);

        return $queue;
    }
}
