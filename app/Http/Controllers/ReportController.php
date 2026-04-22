<?php

namespace App\Http\Controllers;

use App\Models\GuestBook;
use App\Models\Queue;
use App\Support\XlsxReportExporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('manage-reports');

        $report = $this->buildReportData($request);

        return Inertia::render('Reports/Index', [
            'report' => $report,
            'meta' => [
                'title' => 'Laporan Operasional',
                'description' => 'Infografis antrian dan buku tamu lengkap dengan ekspor Excel dan PDF.',
                'dateLabel' => $report['range']['rangeLabel'],
            ],
        ]);
    }

    public function exportExcel(Request $request, XlsxReportExporter $exporter): BinaryFileResponse
    {
        Gate::authorize('manage-reports');

        $report = $this->buildReportData($request);
        $filename = $this->reportFilename($report, 'xlsx');
        $path = $this->temporaryPath($filename);

        $exporter->export($this->buildExcelSheets($report), $path);

        return response()
            ->download($path, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
            ->deleteFileAfterSend(true);
    }

    public function exportPdf(Request $request)
    {
        Gate::authorize('manage-reports');

        $report = $this->buildReportData($request);
        $filename = $this->reportFilename($report, 'pdf');

        return Pdf::loadView('reports.summary', [
            'report' => $report,
        ])
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    protected function buildReportData(Request $request): array
    {
        [$start, $end] = $this->resolveRange($request);

        $queues = Queue::query()
            ->with(['service', 'counter', 'guestBook'])
            ->whereBetween('queue_date', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('queued_at')
            ->get();

        $guestBooks = GuestBook::query()
            ->with(['queue.service', 'queue.counter'])
            ->whereHas('queue', fn ($query) => $query->whereBetween('queue_date', [$start->toDateString(), $end->toDateString()]))
            ->orderByDesc('submitted_at')
            ->get();

        $completedQueues = $queues->where('status', 'completed');
        $averageWaitMinutes = (int) round(
            $completedQueues
                ->filter(fn (Queue $queue) => $queue->called_at && $queue->queued_at)
                ->avg(fn (Queue $queue) => $queue->queued_at->diffInMinutes($queue->called_at)) ?? 0
        );

        $queueStatusMap = [
            'waiting' => 'Menunggu',
            'called' => 'Dipanggil',
            'serving' => 'Diproses',
            'completed' => 'Selesai',
            'skipped' => 'Terlewati',
            'cancelled' => 'Batal',
        ];

        $queueStatus = collect($queueStatusMap)
            ->map(fn (string $label, string $status) => [
                'status' => $status,
                'label' => $label,
                'total' => $queues->where('status', $status)->count(),
            ])
            ->values()
            ->all();

        $serviceBreakdown = $queues
            ->groupBy(fn (Queue $queue) => $queue->service?->name ?? 'Lainnya')
            ->map(fn ($group, string $service) => [
                'service' => $service,
                'total' => $group->count(),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();

        $ratingBreakdown = collect(range(1, 5))
            ->map(fn (int $rating) => [
                'rating' => $rating,
                'total' => $guestBooks->where('rating', $rating)->count(),
            ])
            ->values()
            ->all();

        $recommendVotes = $guestBooks->filter(fn (GuestBook $guestBook) => $guestBook->would_recommend !== null);
        $recommendationRate = $recommendVotes->count() > 0
            ? (int) round(($recommendVotes->where('would_recommend', true)->count() / $recommendVotes->count()) * 100)
            : 0;

        $timeline = collect($this->dateRangeSequence($start, $end))
            ->map(function (Carbon $date) use ($queues, $guestBooks) {
                $day = $date->toDateString();

                return [
                    'date' => $date->translatedFormat('d M'),
                    'queueTotal' => $queues->where('queue_date', $day)->count(),
                    'guestBookTotal' => $guestBooks->filter(function (GuestBook $guestBook) use ($day) {
                        $submittedAt = $guestBook->submitted_at ?? $guestBook->queue?->queued_at;

                        return $submittedAt?->toDateString() === $day;
                    })->count(),
                ];
            })
            ->all();

        $recentQueues = $queues
            ->take(10)
            ->values()
            ->map(fn (Queue $queue) => [
                'ticket' => $queue->ticket_number,
                'service' => $queue->service?->name ?? '-',
                'counter' => $queue->counter?->name ?? 'Belum dipanggil',
                'status' => $queueStatusMap[$queue->status] ?? ucfirst($queue->status),
                'queueDate' => $queue->queue_date?->format('d M Y'),
                'queuedAt' => $queue->queued_at?->format('H:i'),
                'calledAt' => $queue->called_at?->format('H:i') ?? '-',
                'completedAt' => $queue->completed_at?->format('H:i') ?? '-',
                'waitMinutes' => $queue->called_at && $queue->queued_at ? $queue->queued_at->diffInMinutes($queue->called_at) : null,
            ])
            ->all();

        $recentGuestBooks = $guestBooks
            ->take(10)
            ->values()
            ->map(fn (GuestBook $guestBook) => [
                'ticket' => $guestBook->queue?->ticket_number ?? '-',
                'guestName' => $guestBook->guest_name,
                'institution' => $guestBook->institution ?: '-',
                'consultantName' => $guestBook->consultant_name ?: '-',
                'rating' => $guestBook->rating ?? '-',
                'wouldRecommend' => $guestBook->would_recommend === null ? '-' : ($guestBook->would_recommend ? 'Ya' : 'Tidak'),
                'submittedAt' => $guestBook->submitted_at?->format('d M Y H:i') ?? '-',
            ])
            ->all();

        $totalQueues = $queues->count();
        $activeQueues = $queues->whereIn('status', ['waiting', 'called', 'serving'])->count();
        $completedCount = $completedQueues->count();
        $averageRating = (float) ($guestBooks->whereNotNull('rating')->avg('rating') ?? 0);

        return [
            'range' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'startLabel' => $start->translatedFormat('d M Y'),
                'endLabel' => $end->translatedFormat('d M Y'),
                'rangeLabel' => $this->rangeLabel($start, $end),
            ],
            'kpis' => [
                [
                    'label' => 'Total Antrian',
                    'value' => $totalQueues,
                    'note' => $activeQueues.' masih aktif',
                    'tone' => 'teal',
                ],
                [
                    'label' => 'Antrian Selesai',
                    'value' => $completedCount,
                    'note' => $completedCount > 0 ? round(($completedCount / max($totalQueues, 1)) * 100).'% selesai' : 'Belum ada',
                    'tone' => 'emerald',
                ],
                [
                    'label' => 'Rata-rata Tunggu',
                    'value' => str_pad((string) floor($averageWaitMinutes / 60), 2, '0', STR_PAD_LEFT).':'.str_pad((string) ($averageWaitMinutes % 60), 2, '0', STR_PAD_LEFT),
                    'note' => 'dari antrian selesai',
                    'tone' => 'amber',
                ],
                [
                    'label' => 'Buku Tamu',
                    'value' => $guestBooks->count(),
                    'note' => $guestBooks->whereNotNull('feedback')->count().' feedback terisi',
                    'tone' => 'sky',
                ],
                [
                    'label' => 'Rata-rata Rating',
                    'value' => number_format($averageRating, 1),
                    'note' => 'skala 1-5',
                    'tone' => 'rose',
                ],
                [
                    'label' => 'Rekomendasi',
                    'value' => $recommendationRate.'%',
                    'note' => 'responden yang merekomendasikan',
                    'tone' => 'violet',
                ],
            ],
            'queueStatus' => $queueStatus,
            'serviceBreakdown' => $serviceBreakdown,
            'ratingBreakdown' => $ratingBreakdown,
            'timeline' => $timeline,
            'recentQueues' => $recentQueues,
            'recentGuestBooks' => $recentGuestBooks,
            'summary' => [
                'queueTotal' => $totalQueues,
                'completedTotal' => $completedCount,
                'activeTotal' => $activeQueues,
                'guestBookTotal' => $guestBooks->count(),
                'averageWaitMinutes' => $averageWaitMinutes,
                'averageRating' => $averageRating,
                'recommendationRate' => $recommendationRate,
                'topService' => $serviceBreakdown[0]['service'] ?? '-',
            ],
            'queueExportRows' => $this->buildQueueExportRows($queues, $queueStatusMap),
            'guestBookExportRows' => $this->buildGuestBookExportRows($guestBooks),
        ];
    }

    protected function buildExcelSheets(array $report): array
    {
        return [
            [
                'title' => 'Ringkasan',
                'rows' => $this->buildSummarySheetRows($report),
            ],
            [
                'title' => 'Antrian',
                'rows' => $report['queueExportRows'],
            ],
            [
                'title' => 'Buku Tamu',
                'rows' => $report['guestBookExportRows'],
            ],
        ];
    }

    protected function buildSummarySheetRows(array $report): array
    {
        return [
            ['Laporan Operasional', ''],
            ['Periode', $report['range']['startLabel'].' s.d. '.$report['range']['endLabel']],
            ['Total Antrian', $report['summary']['queueTotal']],
            ['Antrian Selesai', $report['summary']['completedTotal']],
            ['Antrian Aktif', $report['summary']['activeTotal']],
            ['Buku Tamu', $report['summary']['guestBookTotal']],
            ['Rata-rata Tunggu (menit)', $report['summary']['averageWaitMinutes']],
            ['Rata-rata Rating', number_format($report['summary']['averageRating'], 1)],
            ['Rekomendasi', $report['summary']['recommendationRate'].'%'],
            ['Layanan Teratas', $report['summary']['topService']],
        ];
    }

    protected function buildQueueExportRows($queues, array $queueStatusMap): array
    {
        $rows = [[
            'Tanggal',
            'Nomor Antrian',
            'Layanan',
            'Loket',
            'Status',
            'Waktu Ambil',
            'Waktu Dipanggil',
            'Waktu Selesai',
            'Lama Tunggu (menit)',
            'Catatan',
        ]];

        foreach ($queues as $queue) {
            $rows[] = [
                $queue->queue_date?->format('d M Y'),
                $queue->ticket_number,
                $queue->service?->name ?? '-',
                $queue->counter?->name ?? '-',
                $queueStatusMap[$queue->status] ?? ucfirst($queue->status),
                $queue->queued_at?->format('d M Y H:i') ?? '-',
                $queue->called_at?->format('d M Y H:i') ?? '-',
                $queue->completed_at?->format('d M Y H:i') ?? '-',
                $queue->called_at && $queue->queued_at ? $queue->queued_at->diffInMinutes($queue->called_at) : null,
                $queue->notes ?: '-',
            ];
        }

        return $rows;
    }

    protected function buildGuestBookExportRows($guestBooks): array
    {
        $rows = [[
            'Waktu Submit',
            'Nomor Antrian',
            'Nama Tamu',
            'Instansi',
            'Nama Konsultan',
            'Rating',
            'Rekomendasi',
            'Feedback',
        ]];

        foreach ($guestBooks as $guestBook) {
            $rows[] = [
                $guestBook->submitted_at?->format('d M Y H:i') ?? '-',
                $guestBook->queue?->ticket_number ?? '-',
                $guestBook->guest_name,
                $guestBook->institution ?: '-',
                $guestBook->consultant_name ?: '-',
                $guestBook->rating ?? '-',
                $guestBook->would_recommend === null ? '-' : ($guestBook->would_recommend ? 'Ya' : 'Tidak'),
                $guestBook->feedback ?: '-',
            ];
        }

        return $rows;
    }

    /**
     * @return array{0:Carbon, 1:Carbon}
     */
    protected function resolveRange(Request $request): array
    {
        $fallbackEnd = now()->endOfDay();
        $fallbackStart = now()->subDays(29)->startOfDay();

        $start = $request->filled('start')
            ? Carbon::parse($request->string('start')->toString())->startOfDay()
            : $fallbackStart;

        $end = $request->filled('end')
            ? Carbon::parse($request->string('end')->toString())->endOfDay()
            : $fallbackEnd;

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    /**
     * @return array<int, Carbon>
     */
    protected function dateRangeSequence(Carbon $start, Carbon $end): array
    {
        $dates = [];
        $cursor = $start->copy()->startOfDay();

        while ($cursor->lessThanOrEqualTo($end->copy()->startOfDay())) {
            $dates[] = $cursor->copy();
            $cursor->addDay();
        }

        return $dates;
    }

    protected function rangeLabel(Carbon $start, Carbon $end): string
    {
        if ($start->isSameDay($end)) {
            return $start->translatedFormat('d F Y');
        }

        return $start->translatedFormat('d M Y').' - '.$end->translatedFormat('d M Y');
    }

    protected function reportFilename(array $report, string $extension): string
    {
        return sprintf(
            'laporan-operasional-%s-sampai-%s.%s',
            Carbon::parse($report['range']['start'])->format('Ymd'),
            Carbon::parse($report['range']['end'])->format('Ymd'),
            $extension
        );
    }

    protected function temporaryPath(string $filename): string
    {
        $directory = storage_path('app/reports');
        File::ensureDirectoryExists($directory);

        return $directory.DIRECTORY_SEPARATOR.$filename;
    }
}
