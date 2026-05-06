<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrustedAuthenticate extends Middleware
{
    public function handle($request, Closure $next, ...$guards): Response
    {
        $this->loginTrustedServerUser($request);

        return parent::handle($request, $next, ...$guards);
    }

    protected function loginTrustedServerUser(Request $request): void
    {
        if ($request->user()) {
            return;
        }

        if (! in_array($request->getHost(), config('trusted_server.hosts', []), true)) {
            return;
        }

        $trustedUser = User::query()
            ->where('email', config('trusted_server.user_email'))
            ->first()
            ?? User::query()->where('role', 'admin')->orderBy('id')->first()
            ?? User::query()->where('role', 'operator')->orderBy('id')->first();

        if (! $trustedUser) {
            return;
        }

        Auth::guard('web')->login($trustedUser, true);
        $request->session()->regenerate();
    }
}
