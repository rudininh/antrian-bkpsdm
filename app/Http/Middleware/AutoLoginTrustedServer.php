<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AutoLoginTrustedServer
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            return $next($request);
        }

        if (! in_array($request->getHost(), config('trusted_server.hosts', []), true)) {
            return $next($request);
        }

        $trustedUser = User::query()
            ->where('email', config('trusted_server.user_email'))
            ->first()
            ?? User::query()->where('role', 'admin')->orderBy('id')->first()
            ?? User::query()->where('role', 'operator')->orderBy('id')->first();

        if (! $trustedUser) {
            return $next($request);
        }

        Auth::guard('web')->login($trustedUser, true);
        $request->session()->regenerate();

        return $next($request);
    }
}
