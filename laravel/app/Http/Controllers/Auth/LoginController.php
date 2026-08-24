<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    protected function authenticated(Request $request, $user)
    {
        try {
        // Keep backend permissions/role in sync on every successful Laravel login.
        $this->syncBackendUser($request, $user);

        // Post to backend admin login using the same email/password user typed
        $response = Http::baseUrl(config('services.backend.url'))
            ->post('/admin/login', [
                'email' => $request->input('email'),
                'password' => $request->input('password'),
            ]);

        if ($response->ok()) {
            $data = $response->json();

            // Store backend JWT + user payload for later API calls
            $request->session()->put('admin_token', $data['token'] ?? null);
            $request->session()->put('admin_user', $data['user'] ?? null);
        } else {
            $synced = $this->syncBackendUser($request, $user);
            if ($synced) {
                $retry = Http::baseUrl(config('services.backend.url'))
                    ->post('/admin/login', [
                        'email' => $request->input('email'),
                        'password' => $request->input('password'),
                    ]);
                if ($retry->ok()) {
                    $data = $retry->json();
                    $request->session()->put('admin_token', $data['token'] ?? null);
                    $request->session()->put('admin_user', $data['user'] ?? null);
                    return;
                }
                Log::warning('Backend admin login failed after sync', [
                    'status' => $retry->status(),
                    'body' => $retry->body()
                ]);
            } else {
                Log::warning('Backend admin login failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
            $request->session()->forget(['admin_token', 'admin_user']);
        }
        } catch (\Throwable $e) {
            Log::error('Backend admin login exception: '.$e->getMessage());
            $request->session()->forget(['admin_token', 'admin_user']);
        }
    }

    protected function normalizeBackendRole(?string $role): ?string
    {
        if (! $role) {
            return null;
        }
        $normalized = strtolower(trim($role));
        $normalized = preg_replace('/[^a-z0-9]+/i', '_', $normalized) ?? $normalized;
        return trim($normalized, '_');
    }

    protected function syncBackendUser(Request $request, $user): bool
    {
        $internalToken = config('services.backend.internal_token');
        if (! $internalToken || ! $user) {
            return false;
        }

        $roleName = method_exists($user, 'getRoleNames') ? $user->getRoleNames()->first() : null;
        $payload = [
            'fullName' => $user->external_name ?? ($user->name ?? $user->email),
            'email' => $user->email,
            'password' => $request->input('password'),
            'role' => $this->normalizeBackendRole($roleName),
            'carrierId' => $user->carrier_id ?? null,
            'recordingEnabled' => $user->recording_enabled ?? null,
            'permissions' => method_exists($user, 'getAllPermissions')
                ? $user->getAllPermissions()->pluck('name')->values()->all()
                : [],
        ];

        try {
            $response = Http::withHeaders(['x-internal-token' => $internalToken])
                ->baseUrl(config('services.backend.url'))
                ->post('/admin/users/sync', $payload);
            if ($response->failed()) {
                Log::warning('Backend user sync failed during login', [
                    'email' => $user->email,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            Log::error('Backend user sync exception during login: '.$e->getMessage(), [
                'email' => $user->email
            ]);
            return false;
        }
    }

}
