<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\Queue;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class PublicQueueController extends Controller
{
    public function index(): Response
    {
        $today = Carbon::today();

        return Inertia::render('Public/TakeQueue', [
            'publicPage' => [
                'title' => 'Ambil Nomor Antrian',
                'subtitle' => '',
            ],
            'services' => $this->servicesForPublic($today),
            'liveCalls' => $this->liveCalls($today),
            'summary' => $this->summary($today),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'service_code' => ['required', 'string', 'size:1'],
        ]);

        $service = $this->resolvePublicServiceByCode($data['service_code']);

        $queue = DB::transaction(function () use ($service) {
            $queuedAt = now();

            // Lock the service row so concurrent requests for the same service serialize cleanly.
            Service::query()->whereKey($service->id)->lockForUpdate()->first();

            return Queue::query()->create([
                'service_id' => $service->id,
                'ticket_number' => $this->generateTicketNumber($service, $queuedAt),
                'queue_date' => $queuedAt->toDateString(),
                'status' => 'waiting',
                'queued_at' => $queuedAt,
            ]);
        });

        return to_route('public.queue.success', $queue);
    }

    public function success(Queue $queue): Response
    {
        $queue->loadMissing('service');

        $today = Carbon::parse($queue->queue_date ?? $queue->queued_at ?? now())->startOfDay();
        $queuesAhead = Queue::query()
            ->where('service_id', $queue->service_id)
            ->whereDate('queue_date', $queue->queue_date)
            ->where('queued_at', '<', $queue->queued_at)
            ->whereIn('status', ['waiting', 'called', 'serving'])
            ->count();

        return Inertia::render('Public/TicketSuccess', [
            'publicPage' => [
                'title' => 'Nomor Antrian Berhasil Dibuat',
                'subtitle' => 'Simpan nomor Anda, lalu pantau panggilan dari layar monitor publik agar tidak terlewat.',
            ],
            'ticket' => [
                'id' => $queue->id,
                'ticketNumber' => $queue->ticket_number,
                'serviceName' => $queue->service?->name,
                'serviceCode' => $queue->service?->code,
                'queuedAt' => $queue->queued_at?->format('H:i'),
                'queueDate' => $queue->queue_date?->translatedFormat('d F Y'),
                'status' => $queue->status,
                'queuesAhead' => $queuesAhead,
            ],
            'liveCalls' => $this->liveCalls($today),
            'summary' => $this->summary($today),
        ]);
    }

    public function monitor(): RedirectResponse
    {
        return Redirect::route('public.queue.index');
    }

    protected function servicesForPublic(Carbon $today)
    {
        $serviceMap = Service::query()
            ->whereIn('code', $this->serviceCatalog()->pluck('code'))
            ->get()
            ->keyBy('code');

        return $this->serviceCatalog()
            ->map(function (array $catalog) use ($today, $serviceMap) {
                /** @var Service|null $service */
                $service = $serviceMap->get($catalog['code']);

                return [
                    'id' => $service?->id,
                    'name' => $catalog['name'],
                    'code' => $catalog['code'],
                    'groupTitle' => $catalog['groupTitle'],
                    'description' => $catalog['description'],
                    'items' => $catalog['items'],
                    'available' => $service?->is_active ?? true,
                    'waitingCount' => $service
                        ? Queue::query()
                            ->where('service_id', $service->id)
                            ->whereDate('queue_date', $today)
                            ->whereIn('status', ['waiting', 'called', 'serving'])
                            ->count()
                        : 0,
                    'calledCount' => $service
                        ? Queue::query()
                            ->where('service_id', $service->id)
                            ->whereDate('queue_date', $today)
                            ->where('status', 'called')
                            ->count()
                        : 0,
                ];
            })
            ->values();
    }

    protected function liveCalls(Carbon $today)
    {
        return Call::query()
            ->with(['queue.service', 'counter'])
            ->whereDate('called_at', $today)
            ->whereIn('status', ['called', 'serving'])
            ->latest('called_at')
            ->get()
            ->unique('queue_id')
            ->take(4)
                ->map(fn (Call $call) => [
                    'id' => $call->id,
                    'ticketNumber' => $call->queue?->ticket_number,
                    'serviceName' => $call->queue?->service?->name,
                    'counterName' => $call->counter?->name ?? 'Meja Receptionist',
                    'status' => $call->status,
                    'calledAt' => $call->called_at?->format('H:i'),
                ])
            ->values();
    }

    protected function summary(Carbon $today): array
    {
        return [
            'waiting' => Queue::query()->whereDate('queue_date', $today)->where('status', 'waiting')->count(),
            'called' => Queue::query()->whereDate('queue_date', $today)->where('status', 'called')->count(),
            'serving' => Queue::query()->whereDate('queue_date', $today)->where('status', 'serving')->count(),
            'completed' => Queue::query()->whereDate('queue_date', $today)->where('status', 'completed')->count(),
        ];
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

    protected function resolvePublicServiceByCode(string $code): Service
    {
        $catalog = $this->serviceCatalog()->firstWhere('code', strtoupper($code));

        abort_unless($catalog, 422, 'Kode layanan tidak valid.');

        return Service::query()->updateOrCreate(
            ['code' => $catalog['code']],
            [
                'name' => $catalog['name'],
                'description' => $catalog['description'],
                'is_active' => true,
            ],
        );
    }

    protected function serviceCatalog(): Collection
    {
        return collect([
            [
                'code' => 'A',
                'groupTitle' => 'Layanan Administrasi Kepegawaian',
                'name' => 'Administrasi Kepegawaian',
                'description' => 'Kenaikan pangkat, KGB, SK CPNS/PNS/PPPK, perubahan data, dan legalisir dokumen.',
                'items' => ['Kenaikan Pangkat (KP)', 'Kenaikan Gaji Berkala (KGB)', 'SK CPNS / PNS / PPPK', 'Perubahan Data', 'Legalisir dokumen'],
            ],
            [
                'code' => 'B',
                'groupTitle' => 'Mutasi & Penempatan',
                'name' => 'Mutasi dan Penempatan',
                'description' => 'Mutasi antar instansi, rotasi jabatan, penempatan awal, dan perpindahan unit kerja.',
                'items' => ['Mutasi antar instansi', 'Rotasi jabatan', 'Penempatan awal', 'Perpindahan unit kerja'],
            ],
            [
                'code' => 'C',
                'groupTitle' => 'Pengembangan Kompetensi',
                'name' => 'Pengembangan Kompetensi',
                'description' => 'Diklat, pelatihan, izin belajar, tugas belajar, dan sertifikasi.',
                'items' => ['Diklat / Pelatihan', 'Izin belajar / tugas belajar', 'Sertifikasi'],
            ],
            [
                'code' => 'D',
                'groupTitle' => 'Disiplin & Status ASN',
                'name' => 'Disiplin dan Status ASN',
                'description' => 'Klarifikasi pelanggaran, proses hukuman disiplin, dan pembinaan ASN.',
                'items' => ['Klarifikasi pelanggaran', 'Proses hukuman disiplin', 'Pembinaan'],
            ],
            [
                'code' => 'E',
                'groupTitle' => 'Kesejahteraan & Hak',
                'name' => 'Kesejahteraan dan Hak',
                'description' => 'Layanan Taspen, pensiun, cuti, dan tunjangan pegawai.',
                'items' => ['Taspen', 'Pensiun', 'Cuti', 'Tunjangan'],
            ],
            [
                'code' => 'F',
                'groupTitle' => 'Layanan Data & Informasi',
                'name' => 'Layanan Data dan Informasi',
                'description' => 'Permintaan data ASN, verifikasi data, dan konsultasi kepegawaian.',
                'items' => ['Permintaan data ASN', 'Verifikasi data', 'Konsultasi kepegawaian'],
            ],
            [
                'code' => 'G',
                'groupTitle' => 'Layanan Umum',
                'name' => 'Layanan Umum',
                'description' => 'Konsultasi umum dan helpdesk aplikasi sebagai layanan buffer.',
                'items' => ['Konsultasi umum', 'Helpdesk aplikasi'],
            ],
        ]);
    }
}
