<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DialerController extends Controller
{
    protected function backend(string $token)
    {
        return Http::withToken($token)->baseUrl(config('services.backend.url'));
    }

    protected function assertDialerPermission(Request $request): void
    {
        if (! $request->user() || ! $request->user()->can('dialer.create_call')) {
            abort(403, __('You do not have permission to create calls.'));
        }
    }

    protected function backendToken(Request $request): ?string
    {
        return $request->session()->get('admin_token');
    }

    public function index(Request $request)
    {
        $this->assertDialerPermission($request);

        $user = $request->user();
        $sipCredential = $user?->sipCredential;
        $webrtcConfig = [
            'wsUrl' => config('services.webrtc.ws'),
            'domain' => config('services.webrtc.domain'),
            'username' => $sipCredential?->sip_username,
            'password' => $sipCredential?->sip_password,
            'iceServers' => config('services.webrtc.ice_servers'),
        ];
        $webrtcError = null;
        if (! $sipCredential || ! $sipCredential->sip_username || ! $sipCredential->sip_password) {
            $webrtcError = __('SIP credentials are not configured for this user.');
        }

        return view('backend.pages.dialer.index', [
            'webrtcConfig' => $webrtcConfig,
            'webrtcError' => $webrtcError,
        ]);
    }

    public function dial(Request $request)
    {
        $this->assertDialerPermission($request);

        $token = $this->backendToken($request);
        if (! $token) {
            return response()->json([
                'ok' => false,
                'message' => 'Backend token missing. Please login again.',
            ], 401);
        }

        $data = $request->validate([
            'destination' => ['required', 'string'],
            'callerId' => ['nullable', 'string'],
        ]);

        $payload = [
            'destination' => $data['destination'],
        ];

        if (filled($data['callerId'] ?? null)) {
            $payload['callerId'] = $data['callerId'];
        }

        $resp = $this->backend($token)->post('/calls', $payload);

        return response()->json($resp->json(), $resp->status());
    }

    protected function proxyRequest(Request $request, string $method, string $endpoint, array $payload = [])
    {
        $this->assertDialerPermission($request);

        $token = $this->backendToken($request);
        if (! $token) {
            return response()->json([
                'ok' => false,
                'message' => 'Backend token missing. Please login again.',
            ], 401);
        }

        $client = $this->backend($token);

        $response = match (strtolower($method)) {
            'get' => $client->get($endpoint),
            'post' => $client->post($endpoint, $payload),
            default => throw new \InvalidArgumentException("Unsupported method [{$method}]"),
        };

        return response()->json($response->json(), $response->status());
    }

    public function status(Request $request, string $uuid)
    {
        return $this->proxyRequest($request, 'get', "/calls/{$uuid}");
    }

    public function mute(Request $request, string $uuid)
    {
        return $this->proxyRequest($request, 'post', "/calls/{$uuid}/mute");
    }

    public function unmute(Request $request, string $uuid)
    {
        return $this->proxyRequest($request, 'post', "/calls/{$uuid}/unmute");
    }

    public function hangup(Request $request, string $uuid)
    {
        return $this->proxyRequest($request, 'post', "/calls/{$uuid}/hangup");
    }

    public function dtmf(Request $request, string $uuid)
    {
        $data = $request->validate([
            'digits' => ['required', 'string'],
        ]);

        return $this->proxyRequest($request, 'post', "/calls/{$uuid}/dtmf", $data);
    }
}
