<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesAiAgentLabels;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignRun;
use App\Models\User;
use App\Services\VicidialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DialerController extends Controller
{
    use ResolvesAiAgentLabels;

    protected function assertCampaignPermission(Request $request): void
{
    if (! $request->user() || ! $request->user()->can('campaign.play')) {
        abort(403, __('You do not have permission to run campaigns.'));
    }
}

    protected function backend(string $token)
    {
        return Http::withToken($token)
            ->withHeaders(['x-internal-token' => config('services.backend.internal_token')])
            ->baseUrl(config('services.backend.url'));
    }

    protected function requireBackendToken(Request $request): string
    {
        $token = $this->backendToken($request);
        if (! $token) {
            abort(401, __('Backend token missing. Please login again.'));
        }

        return $token;
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
        $dialerProvider = $user?->dialer_provider ?? 'freeswitch';
        $backendSocketUrl = config('services.backend.ws_url');
        if (! $backendSocketUrl) {
            $socketScheme = $request->isSecure() ? 'wss' : 'ws';
            $backendSocketUrl = $socketScheme.'://'.$request->getHost().':4000';
        }
        $dialerProviders = [];
        if ($sipCredential?->sip_username && $sipCredential?->sip_password) {
            $dialerProviders['freeswitch'] = __('FreeSWITCH');
        }
        if (config('vicidial.enabled')) {
            try {
                app(VicidialService::class)->browserConfig((int) $user->id);
                $dialerProviders['vicidial'] = __('VICIdial');
            } catch (\Throwable $error) {
                // Leave VICIdial unavailable in the selector until its agent and phone config are complete.
            }
        }
        $wsUrl = config('services.webrtc.ws');
        $webrtcConfig = [
            'wsUrl' => $wsUrl,
            'domain' => config('services.webrtc.domain'),
            'username' => $sipCredential?->sip_username,
            'password' => $sipCredential?->sip_password,
            'iceServers' => config('services.webrtc.ice_servers'),
        ];
        $webrtcError = null;
        if (! $sipCredential || ! $sipCredential->sip_username || ! $sipCredential->sip_password) {
            $webrtcError = __('SIP credentials are not configured for this user.');
        }

        $vicidial = [
            'enabled' => $dialerProvider === 'vicidial',
            'apiReady' => false,
            'webrtc' => null,
            'agentUrl' => null,
            'agentUser' => null,
            'dispositions' => [],
            'leadLookup' => false,
            'conferenceTargets' => [],
            'commandUrl' => route('admin.vicidial.command'),
            'csrf' => csrf_token(),
            'error' => null,
        ];
        if ($dialerProvider === 'vicidial') {
            $webrtcConfig = [
                'wsUrl' => null,
                'domain' => null,
                'username' => null,
                'password' => null,
                'iceServers' => [],
            ];
            $webrtcError = null;
            try {
                $service = app(VicidialService::class);
                $agent = $service->agent((int) $user->id);
                $vicidial['webrtc'] = $webrtcConfig = $service->browserConfig((int) $user->id);
                $vicidial['apiReady'] = $service->apiReady((int) $user->id);
                $vicidial['agentUser'] = $agent['agent_user'];
                $vicidial['agentUrl'] = str_starts_with((string) config('vicidial.agent_url'), 'https://')
                    ? config('vicidial.agent_url')
                    : null;
                $vicidial['dispositions'] = config('vicidial.dispositions', []);
                $vicidial['conferenceTargets'] = array_keys($service->conferenceTargets());
                $vicidial['leadLookup'] = ! empty($agent['crm_agent_id']) && ! empty($agent['crm_team_id']);
            } catch (\Throwable $error) {
                $vicidial['error'] = $error->getMessage();
                $webrtcError = $error->getMessage();
            }
        }

        $aiAgentSettings = null;
        try {
            $aiAgentSettings = \App\Services\AiAgentSettingsService::get();
        } catch (\Throwable $e) {
            $aiAgentSettings = null;
        }

        $campaigns = collect();
        $run = null;
        $agents = User::orderBy('external_name')
            ->get(['id', 'external_name', 'email']);

        if ($user && $user->can('campaign.play')) {
            $campaigns = Campaign::latest()->get(['id', 'list_id', 'list_name']);
            $run = CampaignRun::where('user_id', $user->id)->latest('id')->first();
        }

        return view('backend.pages.dialer.index', [
            'webrtcConfig' => $webrtcConfig,
            'webrtcError' => $webrtcError,
            'dialerProvider' => $dialerProvider,
            'dialerProviders' => $dialerProviders,
            'vicidial' => $vicidial,
            'aiAgentSettings' => $aiAgentSettings,
            'aiAgentBoxTitle' => $this->aiAgentBoxText('ai_box_title', 'box_title'),
            'aiAgentBoxSubtitle' => $this->aiAgentBoxText('ai_box_subtitle', 'box_subtitle'),
            'campaigns' => $campaigns,
            'run' => $run,
            'agents' => $agents,
            'contactPermissions' => [
                'view' => (bool) $user?->can('contacts.view'),
                'create' => (bool) $user?->can('contacts.create'),
                'edit' => (bool) $user?->can('contacts.edit'),
                'delete' => (bool) $user?->can('contacts.delete'),
                'comment' => (bool) $user?->can('contacts.comment'),
                'labels' => (bool) $user?->can('contacts.labels'),
            ],
            'inboundSocket' => [
                'url' => $backendSocketUrl,
                'userId' => $user?->id,
            ],
        ]);
    }

    public function selectProvider(Request $request)
    {
        $this->assertDialerPermission($request);
        $data = $request->validate([
            'provider' => ['required', 'in:freeswitch,vicidial'],
        ]);
        $user = $request->user();

        if ($data['provider'] === 'vicidial') {
            try {
                app(VicidialService::class)->browserConfig((int) $user->id);
            } catch (\Throwable $error) {
                return back()->withErrors(['provider' => $error->getMessage()]);
            }
        } elseif (! $user->sipCredential?->sip_username || ! $user->sipCredential?->sip_password) {
            return back()->withErrors(['provider' => __('FreeSWITCH SIP credentials are not configured for this user.')]);
        }

        $user->dialer_provider = $data['provider'];
        $user->save();

        try {
            $internalToken = config('services.backend.internal_token');
            if (! $internalToken) {
                throw new \RuntimeException('Backend internal token is not configured.');
            }
            $response = Http::withHeaders(['x-internal-token' => $internalToken])
                ->baseUrl(config('services.backend.url'))
                ->post('/admin/users/sync', [
                    'email' => $user->email,
                    'fullName' => $user->external_name ?: $user->email,
                    'dialerProvider' => $user->dialer_provider,
                ]);
            if ($response->failed()) {
                throw new \RuntimeException('Backend user sync returned HTTP '.$response->status());
            }
        } catch (\Throwable $error) {
            Log::warning('Dialer provider saved but backend sync failed', [
                'user_id' => $user->id,
                'provider' => $user->dialer_provider,
                'error' => $error->getMessage(),
            ]);
            return redirect()->route('admin.dialer.index')
                ->with('warning', __('Provider changed locally, but call-routing sync failed. Refresh or contact an administrator.'));
        }

        return redirect()->route('admin.dialer.index')
            ->with('status', __('Phone provider changed to :provider.', ['provider' => $data['provider'] === 'vicidial' ? 'VICIdial' : 'FreeSWITCH']));
    }

    public function dial(Request $request)
    {
        $this->assertDialerPermission($request);
        if ($request->user()?->dialer_provider === 'vicidial') {
            return response()->json([
                'ok' => false,
                'message' => __('Manual dialing is handled by the active VICIdial agent session.'),
            ], 409);
        }

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
            'put' => $client->put($endpoint, $payload),
            default => throw new \InvalidArgumentException("Unsupported method [{$method}]"),
        };

        return response()->json($response->json(), $response->status());
    }

    public function status(Request $request, string $uuid)
    {
        return $this->proxyRequest($request, 'get', "/calls/{$uuid}");
    }

    public function lastCall(Request $request)
    {
        return $this->proxyRequest($request, 'get', '/calls/last');
    }

    public function updateNotes(Request $request, string $uuid)
    {
        $data = $request->validate([
            'notes' => ['present', 'string', 'max:5000'],
        ]);

        return $this->proxyRequest($request, 'put', "/calls/{$uuid}/notes", $data);
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
        $data = $request->validate([
            'durationSeconds' => ['nullable', 'integer', 'min:0'],
        ]);

        return $this->proxyRequest($request, 'post', "/calls/{$uuid}/hangup", $data);
    }

    public function dtmf(Request $request, string $uuid)
    {
        $data = $request->validate([
            'digits' => ['required', 'string'],
        ]);

        return $this->proxyRequest($request, 'post', "/calls/{$uuid}/dtmf", $data);
    }

    public function decline(Request $request, string $uuid)
    {
        return $this->proxyRequest($request, 'post', "/calls/{$uuid}/decline");
    }

    public function aiAgentSettings(Request $request)
    {
        return $this->proxyRequest($request, 'get', '/ai-agent');
    }

    public function updateAiAgentSettings(Request $request)
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            // Free text on purpose: the duty, greeting, and objective may be
            // anything the administrator needs, so only length is bounded.
            'role' => ['nullable', 'string', 'max:200'],
            'greeting' => ['nullable', 'string', 'max:1000'],
            'goal' => ['nullable', 'string', 'max:2000'],
            'mode' => ['required', 'in:lead,assist,qualify'],
            'voice' => ['required', 'in:man,woman,male,female,professional,warm,confident'],
            'humanHandoff' => ['required', 'boolean'],
            'callDirection' => ['required', 'in:inbound,outbound,both'],
        ]);
        return $this->proxyRequest($request, 'put', '/ai-agent', $data);
    }

    public function aiAssist(Request $request)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'callNotes' => ['nullable', 'string', 'max:5000'],
        ]);

        return $this->proxyRequest($request, 'post', '/ai-agent/assist', $data);
    }

    public function startCampaign(Request $request)
    {
        $this->assertCampaignPermission($request);

        $data = $request->validate([
            'campaign_id' => ['required', 'integer', 'min:1'],
            'agent' => ['required', 'string', 'max:100'],
        ]);

        $token = $this->requireBackendToken($request);

        $resp = $this->backend($token)->post('/dialer/campaign/start', [
            'campaignId' => $data['campaign_id'],
            'agent' => $data['agent'],
            'leadScope' => 'all',
        ]);

        return response()->json($resp->json(), $resp->status());
    }

    public function restartFailedCampaign(Request $request)
    {
        $this->assertCampaignPermission($request);

        $data = $request->validate([
            'campaign_id' => ['required', 'integer', 'min:1'],
            'agent' => ['required', 'string', 'max:100'],
        ]);

        $token = $this->requireBackendToken($request);

        $resp = $this->backend($token)->post('/dialer/campaign/start', [
            'campaignId' => $data['campaign_id'],
            'agent' => $data['agent'],
            'leadScope' => 'failed',
        ]);

        return response()->json($resp->json(), $resp->status());
    }

    public function stopCampaign(Request $request)
    {
        $this->assertCampaignPermission($request);

        $token = $this->requireBackendToken($request);

        $resp = $this->backend($token)->post('/dialer/campaign/stop');

        return response()->json($resp->json(), $resp->status());
    }

    public function nextLead(Request $request)
    {
        $this->assertCampaignPermission($request);

        $validated = $request->validate([
            'last_lead_id' => ['nullable', 'integer', 'min:1'],
            'last_lead_status' => ['nullable', 'in:called,failed'],
            'lastLeadId' => ['nullable', 'integer', 'min:1'],
            'lastLeadStatus' => ['nullable', 'in:called,failed'],
            'lead_scope' => ['nullable', 'in:all,failed'],
            'leadScope' => ['nullable', 'in:all,failed'],
        ]);

        $lastLeadId = $validated['last_lead_id'] ?? $validated['lastLeadId'] ?? null;
        $lastLeadStatus = $validated['last_lead_status'] ?? $validated['lastLeadStatus'] ?? null;
        $leadScope = $validated['lead_scope'] ?? $validated['leadScope'] ?? 'all';

        $query = [];

        if ($lastLeadId) {
            $query['lastLeadId'] = $lastLeadId;
        }

        if ($lastLeadStatus) {
            $query['lastLeadStatus'] = $lastLeadStatus;
        }
        $query['leadScope'] = $leadScope;

        $token = $this->requireBackendToken($request);

        $resp = $this->backend($token)->get('/dialer/campaign/next', $query);

        return response()->json($resp->json(), $resp->status());
    }
}
