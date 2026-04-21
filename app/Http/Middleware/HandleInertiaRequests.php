<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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
        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'auth' => [
                'user' => $request->user()?->only('id', 'name', 'email', 'role'),
            ],
            'permissions' => [
                'manageMasterData' => $request->user()?->can('manage-master-data') ?? false,
                'manageQueues' => $request->user()?->can('manage-queues') ?? false,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
            'urls' => [
                'home' => Route::has('home') ? route('home') : '/',
                'login' => Route::has('login') ? route('login') : '/login',
                'dashboard' => Route::has('dashboard') ? route('dashboard') : '/dashboard',
                'publicQueueIndex' => Route::has('public.queue.index') ? route('public.queue.index') : '/ambil-antrian',
                'publicQueueStore' => Route::has('public.queue.store') ? route('public.queue.store') : '/ambil-antrian',
                'publicMonitor' => Route::has('public.monitor') ? route('public.monitor') : '/monitor-publik',
                'logoKotaBanjarmasin' => rtrim($request->root(), '/').'/images/logo-kota-banjarmasin.png',
            ],
        ];
    }
}
