<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\GuestBook;
use App\Models\Queue;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_reports_page(): void
    {
        $this->seedReportData();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get(route('reports.index', ['start' => Carbon::today()->subDay()->toDateString(), 'end' => Carbon::today()->toDateString()]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Index')
                ->where('report.summary.queueTotal', 2)
                ->where('report.summary.guestBookTotal', 1)
                ->where('report.summary.topService', 'Pelayanan Umum')
            );
    }

    public function test_operator_can_view_reports_page(): void
    {
        $this->seedReportData();

        $operator = User::factory()->create([
            'role' => 'operator',
        ]);

        $this->actingAs($operator)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Reports/Index'));
    }

    public function test_reports_can_be_exported_to_excel(): void
    {
        $this->seedReportData();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get(route('reports.export.excel', [
                'start' => Carbon::today()->subDay()->toDateString(),
                'end' => Carbon::today()->toDateString(),
            ]))
            ->assertDownload('laporan-operasional-'.Carbon::today()->subDay()->format('Ymd').'-sampai-'.Carbon::today()->format('Ymd').'.xlsx');
    }

    public function test_reports_can_be_exported_to_pdf(): void
    {
        $this->seedReportData();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get(route('reports.export.pdf', [
                'start' => Carbon::today()->subDay()->toDateString(),
                'end' => Carbon::today()->toDateString(),
            ]))
            ->assertDownload('laporan-operasional-'.Carbon::today()->subDay()->format('Ymd').'-sampai-'.Carbon::today()->format('Ymd').'.pdf');
    }

    protected function seedReportData(): void
    {
        $service = Service::create([
            'name' => 'Pelayanan Umum',
            'code' => 'PU',
            'description' => 'Layanan umum',
            'is_active' => true,
        ]);

        $counter = Counter::create([
            'name' => 'Loket 1',
            'code' => 'L1',
            'location' => 'Depan',
            'is_active' => true,
        ]);

        $completedQueue = Queue::create([
            'service_id' => $service->id,
            'counter_id' => $counter->id,
            'ticket_number' => 'A001',
            'queue_date' => Carbon::today(),
            'status' => 'completed',
            'queued_at' => Carbon::today()->setTime(8, 0),
            'called_at' => Carbon::today()->setTime(8, 15),
            'started_at' => Carbon::today()->setTime(8, 16),
            'completed_at' => Carbon::today()->setTime(8, 30),
            'notes' => 'Selesai',
        ]);

        $waitingQueue = Queue::create([
            'service_id' => $service->id,
            'counter_id' => null,
            'ticket_number' => 'A002',
            'queue_date' => Carbon::today(),
            'status' => 'waiting',
            'queued_at' => Carbon::today()->setTime(9, 0),
            'called_at' => null,
            'started_at' => null,
            'completed_at' => null,
            'notes' => null,
        ]);

        GuestBook::create([
            'queue_id' => $completedQueue->id,
            'guest_name' => 'Andi',
            'institution' => 'Dinas A',
            'phone_number' => '08123456789',
            'visit_purpose' => 'Konsultasi',
            'consultant_name' => 'Bu Rina',
            'rating' => 5,
            'feedback' => 'Bagus',
            'would_recommend' => true,
            'submitted_at' => Carbon::today()->setTime(9, 0),
        ]);

        $waitingQueue->refresh();
    }
}
