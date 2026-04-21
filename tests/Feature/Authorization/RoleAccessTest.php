<?php

namespace Tests\Feature\Authorization;

use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_master_data_pages(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get(route('services.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('counters.index'))
            ->assertOk();
    }

    public function test_operator_cannot_access_master_data_pages(): void
    {
        $operator = User::factory()->create([
            'role' => 'operator',
        ]);

        $this->actingAs($operator)
            ->get(route('services.index'))
            ->assertForbidden();
    }

    public function test_operator_can_access_queue_and_monitoring_pages(): void
    {
        $operator = User::factory()->create([
            'role' => 'operator',
        ]);

        $service = Service::query()->create([
            'name' => 'Verifikasi',
            'code' => 'A',
            'is_active' => true,
        ]);

        Counter::query()->create([
            'name' => 'Loket 1',
            'code' => 'L1',
            'is_active' => true,
        ]);

        Queue::query()->create([
            'service_id' => $service->id,
            'ticket_number' => 'A-001',
            'queue_date' => now()->toDateString(),
            'status' => 'waiting',
            'queued_at' => now(),
        ]);

        $this->actingAs($operator)
            ->get(route('queues.index'))
            ->assertOk();

        $this->actingAs($operator)
            ->get(route('monitoring.index'))
            ->assertOk();
    }
}
