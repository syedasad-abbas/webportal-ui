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
            // Login to Laravel succeeded, but backend auth failed
            Log::warning('Backend admin login failed', ['status' => $response->status(), 'body' => $response->body()]);
            $request->session()->forget(['admin_token', 'admin_user']);
        }
    } catch (\Throwable $e) {
        Log::error('Backend admin login exception: '.$e->getMessage());
        $request->session()->forget(['admin_token', 'admin_user']);
    }
}

}
