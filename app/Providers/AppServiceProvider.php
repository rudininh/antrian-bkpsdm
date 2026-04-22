<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage-master-data', fn (User $user) => $user->isAdmin());
        Gate::define('manage-queues', fn (User $user) => $user->isAdmin() || $user->isOperator());
        Gate::define('manage-system', fn (User $user) => $user->isAdmin() || $user->isOperator());
        Gate::define('manage-reports', fn (User $user) => $user->isAdmin() || $user->isOperator());

        Vite::prefetch(concurrency: 3);
    }
}
