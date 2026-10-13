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

    public function index(Request $request, VicidialService $service)
    {
        $this->authorizeAgent($request);
        $agent = $service->agent($request->user()->id);
        $error = null;
        $webrtc = null;
        try {
            $webrtc = $service->browserConfig($request->user()->id);
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }

        return response()->view('backend.pages.dialer.vicidial', [
            'agentUser' => $agent['agent_user'],
            'agentUrl' => $webrtc && str_starts_with((string) config('vicidial.agent_url'), 'https://') ? config('vicidial.agent_url') : null,
            'apiReady' => $service->apiReady(),
            'webrtc' => $webrtc,
            'configurationError' => $error,
            'leadLookup' => ! empty($agent['crm_agent_id']) && ! empty($agent['crm_team_id']),
            'dispositions' => config('vicidial.dispositions', []),
        ])->header('Cache-Control', 'no-store, private');
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
