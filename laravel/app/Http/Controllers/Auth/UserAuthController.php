<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UserAuthController extends Controller
{
    public function showLogin()
    {
        return view('backend.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $request->session()->regenerate();

        try {
            // Keep backend permissions/role in sync on every successful Laravel login.
            $this->syncBackendUser($request);

            $response = Http::baseUrl(config('services.backend.url'))
                ->post('/admin/login', [
                    'email' => $request->input('email'),
                    'password' => $request->input('password'),
                ]);

            if ($response->ok()) {
                $data = $response->json();
                $request->session()->put('admin_token', $data['token'] ?? null);
                $request->session()->put('admin_user', $data['user'] ?? null);
            } else {
                $synced = $this->syncBackendUser($request);
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
                        return redirect()->intended('/admin');
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

        return redirect()->intended('/admin');
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

    protected function syncBackendUser(Request $request): bool
    {
        $internalToken = config('services.backend.internal_token');
        $user = $request->user();
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

    public function logout(Request $request)
    {
        $request->session()->forget(['admin_token', 'admin_user']);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
