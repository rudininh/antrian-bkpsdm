<?php

namespace App\Http\Middleware;

use App\Models\Queue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Carbon;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $today = Carbon::today();
        $waitingQuery = Queue::query()
            ->with('service')
            ->whereDate('queue_date', $today)
            ->where('status', 'waiting');

        $waitingCount = (clone $waitingQuery)->count();
        $nextWaitingQueue = $waitingCount > 0
            ? (clone $waitingQuery)->orderBy('queued_at')->first()
            : null;
        $nextWaitingMinutes = $nextWaitingQueue && $nextWaitingQueue->queued_at
            ? max(0, $nextWaitingQueue->queued_at->diffInMinutes(now()))
            : null;
        $nextQueuedAtIso = $nextWaitingQueue?->queued_at?->toIso8601String();

        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'auth' => [
                'user' => $request->user()?->only('id', 'name', 'email', 'role'),
            ],
            'permissions' => [
                'manageMasterData' => $request->user()?->can('manage-master-data') ?? false,
                'manageQueues' => $request->user()?->can('manage-queues') ?? false,
                'manageSystem' => $request->user()?->can('manage-system') ?? false,
                'manageReports' => $request->user()?->can('manage-reports') ?? false,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'queueAlert' => [
                'waitingCount' => $waitingCount,
                'hasWaiting' => $waitingCount > 0,
                'nextTicketNumber' => $nextWaitingQueue?->ticket_number,
                'nextServiceName' => $nextWaitingQueue?->service?->name,
                'nextQueuedAt' => $nextWaitingQueue?->queued_at?->format('H:i'),
                'nextQueuedAtIso' => $nextQueuedAtIso,
                'nextWaitingMinutes' => $nextWaitingMinutes,
                'nextWaitingLabel' => $nextWaitingMinutes !== null
                    ? $this->formatWaitingMinutes($nextWaitingMinutes)
                    : null,
            ],
            'urls' => [
                'home' => Route::has('home') ? route('home') : '/',
                'login' => Route::has('login') ? route('login') : '/login',
                'dashboard' => Route::has('dashboard') ? route('dashboard') : '/dashboard',
                'publicQueueIndex' => Route::has('public.queue.index') ? route('public.queue.index') : '/ambil-antrian',
                'publicQueueStore' => Route::has('public.queue.store') ? route('public.queue.store') : '/ambil-antrian',
                'publicGuestBookKiosk' => Route::has('public.guest-book.kiosk') ? route('public.guest-book.kiosk') : '/buku-tamu',
                'publicMonitor' => Route::has('public.monitor') ? route('public.monitor') : '/monitor-publik',
                'logoKotaBanjarmasin' => rtrim($request->root(), '/').'/images/logo-kota-banjarmasin.png',
            ],
        ];
    }

    protected function formatWaitingMinutes(int $minutes): string
    {
        if ($minutes <= 0) {
            return 'baru saja';
        }

        if ($minutes === 1) {
            return '1 menit';
        }

        return $minutes.' menit';
    }
}
