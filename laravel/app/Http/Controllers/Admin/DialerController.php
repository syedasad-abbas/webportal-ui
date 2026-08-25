<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignRun;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DialerController extends Controller
{    
    protected function assertCampaignPermission(Request $request): void
{
    if (! $request->user() || ! $request->user()->can('campaign.play')) {
        abort(403, __('You do not have permission to run campaigns.'));
    }
}

    protected function backend(string $token)
    {
        return Http::withToken($token)->baseUrl(config('services.backend.url'));
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
        $sipHost = $request->getHost();
        $wsUrl = config('services.webrtc.ws') ?: sprintf(
            '%s://%s:%d',
            $request->isSecure() ? 'wss' : 'ws',
            $sipHost,
            $request->isSecure() ? 7443 : 5066
        );
        $webrtcConfig = [
            'wsUrl' => $wsUrl,
            'domain' => config('services.webrtc.domain') ?: $sipHost,
            'username' => $sipCredential?->sip_username,
            'password' => $sipCredential?->sip_password,
            'iceServers' => config('services.webrtc.ice_servers'),
        ];
        $webrtcError = null;
        if (! $sipCredential || ! $sipCredential->sip_username || ! $sipCredential->sip_password) {
            $webrtcError = __('SIP credentials are not configured for this user.');
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
                'url' => config('services.backend.ws_url'),
                'userId' => $user?->id,
            ],
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
