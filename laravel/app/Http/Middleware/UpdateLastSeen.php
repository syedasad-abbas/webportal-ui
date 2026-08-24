<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeen
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = Auth::guard('web')->user();
        if ($user) {
            $thresholdSeconds = (int) config('session.last_seen_update_seconds', 60);
            $shouldUpdate = $user->last_seen_at === null;
            if (! $shouldUpdate && $user->last_seen_at instanceof Carbon) {
                $shouldUpdate = $user->last_seen_at->lt(now()->subSeconds($thresholdSeconds));
            }

            if ($shouldUpdate) {
                $user->forceFill(['last_seen_at' => now()])->save();
            }
        }

        return $response;
    }
}
