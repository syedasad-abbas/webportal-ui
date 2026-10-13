<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\VicidialService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class VicidialController extends Controller
{
    private function authorizeAgent(Request $request): void
    {
        abort_unless($request->user()?->can('dialer.create_call'), 403);
    }

    public function caCertificate(Request $request)
    {
        $this->authorizeAgent($request);
        $path = config('vicidial.ca_bundle');
        abort_unless(is_string($path) && is_file($path) && is_readable($path), 404, 'VICIdial CA certificate is not configured.');

        return response()->download($path, 'vicidial-lab-ca.crt', [
            'Content-Type' => 'application/x-x509-ca-cert',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function freeswitchCaCertificate(Request $request)
    {
        $this->authorizeAgent($request);
        $path = public_path('certs/freeswitch-wss-ca.crt');
        abort_unless(is_file($path) && is_readable($path), 404, 'FreeSWITCH WSS CA certificate is not available.');

        return response()->download($path, 'freeswitch-wss-ca.crt', [
            'Content-Type' => 'application/x-x509-ca-cert',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function index(Request $request)
    {
        abort_unless($request->user()?->can('dialer.create_call'), 403);
        return redirect()->route('admin.dialer.index');
    }

    public function command(Request $request, VicidialService $service)
    {
        $this->authorizeAgent($request);
        $service->agent($request->user()->id);
        $data = $request->validate([
            'action' => ['required', Rule::in(['connect', 'ready', 'pause', 'hangup', 'disposition', 'lead'])],
            'value' => ['nullable', 'string', 'max:20'],
        ]);
        try {
            return response()->json($service->command($request->user()->id, $data['action'], $data['value'] ?? null))
                ->header('Cache-Control', 'no-store, private');
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }
    }
}
