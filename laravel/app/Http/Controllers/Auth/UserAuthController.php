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
                Log::warning('Backend admin login failed', ['status' => $response->status(), 'body' => $response->body()]);
                $request->session()->forget(['admin_token', 'admin_user']);
            }
        } catch (\Throwable $e) {
            Log::error('Backend admin login exception: '.$e->getMessage());
            $request->session()->forget(['admin_token', 'admin_user']);
        }

        return redirect()->intended('/admin');
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
