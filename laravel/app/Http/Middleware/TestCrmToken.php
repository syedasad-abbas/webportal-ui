<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TestCrmToken
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(config('crm.test_enabled'), 404);
        $token = (string) config('crm.test_token');
        abort_unless(strlen($token) >= 32 && hash_equals($token, (string) $request->bearerToken()), 401);
        return $next($request);
    }
}
