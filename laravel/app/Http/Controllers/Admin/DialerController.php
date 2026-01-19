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

    public function showCall(Request $request, string $uuid)
    {
        $this->assertDialerPermission($request);

        $token = $this->backendToken($request);
        if (! $token) {
            return response()->json([
                'ok' => false,
                'message' => 'Backend token missing. Please login again.',
            ], 401);
        }

        $resp = $this->backend($token)->get("/calls/{$uuid}");

        return response()->json($resp->json(), $resp->status());
    }
}
