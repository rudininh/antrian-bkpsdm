<?php

namespace Tests\Feature;

use App\Models\Call;
use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_monitoring_page_without_login(): void
    {
        $this->get(route('monitoring.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Index')
            );
    }

    public function test_guest_can_call_waiting_queue_without_login(): void
    {
        $queue = $this->createQueue('waiting');

        $this->post(route('monitoring.call', $queue))
            ->assertRedirect();

        $this->assertDatabaseHas('queues', [
            'id' => $queue->id,
            'status' => 'called',
        ]);

        $this->assertDatabaseHas('calls', [
            'queue_id' => $queue->id,
            'status' => 'called',
        ]);
    }

    public function test_guest_can_manage_active_call_actions_without_login(): void
    {
        $actions = [
            'recall' => 'called',
            'start' => 'serving',
            'complete' => 'completed',
            'skip' => 'skipped',
        ];

        foreach ($actions as $action => $expectedStatus) {
            $queue = $this->createQueueWithCall('called', strtoupper($action).'-001');

            $this->post(route("monitoring.{$action}", $queue))
                ->assertRedirect();

            $this->assertDatabaseHas('queues', [
                'id' => $queue->id,
                'status' => $expectedStatus,
            ]);

            $this->assertDatabaseHas('calls', [
                'queue_id' => $queue->id,
                'status' => $expectedStatus,
            ]);
        }
    }

    protected function createQueue(string $status, string $ticketNumber = 'A-001'): Queue
    {
        $service = Service::query()->firstOrCreate(
            ['code' => 'A'],
            [
                'name' => 'Administrasi Kepegawaian',
                'description' => 'Layanan administrasi',
                'is_active' => true,
            ],
        );

        return Queue::query()->create([
            'service_id' => $service->id,
            'ticket_number' => $ticketNumber,
            'queue_date' => now()->toDateString(),
            'status' => $status,
            'queued_at' => now(),
            'called_at' => in_array($status, ['called', 'serving'], true) ? now() : null,
            'started_at' => $status === 'serving' ? now() : null,
        ]);
    }

    protected function createQueueWithCall(string $status, string $ticketNumber): Queue
    {
        $counter = Counter::query()->firstOrCreate(
            ['code' => 'RCP'],
            [
                'name' => 'Receptionist',
                'location' => 'Meja Receptionist',
                'is_active' => true,
            ],
        );

        $queue = $this->createQueue($status, $ticketNumber);
        $queue->update(['counter_id' => $counter->id]);

        Call::query()->create([
            'queue_id' => $queue->id,
            'counter_id' => $counter->id,
            'status' => $status,
            'called_at' => now(),
            'started_at' => $status === 'serving' ? now() : null,
        ]);

        return $queue;
    }
}
