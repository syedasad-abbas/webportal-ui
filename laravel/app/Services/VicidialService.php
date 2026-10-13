<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class VicidialService
{
    public function agent(int $userId): array
    {
        abort_unless(config('vicidial.enabled'), 404);
        $agent = config('vicidial.agents', [])[$userId] ?? null;
        abort_unless(is_array($agent) && ! empty($agent['agent_user']), 403, 'No VICIdial agent is assigned to your account.');

        return $agent;
    }

    public function browserConfig(int $userId): array
    {
        $agent = $this->agent($userId);
        $this->requireUrl(config('vicidial.ws_url'), 'wss', 'VICIDIAL_WSS_URL');
        foreach ([config('vicidial.sip_domain'), $agent['sip_username'] ?? '', $agent['sip_password'] ?? ''] as $value) {
            if (! is_string($value) || trim($value) === '') {
                throw new RuntimeException('VICIdial configuration is incomplete. Ask your administrator to configure your phone account.');
            }
        }

        return [
            'wsUrl' => config('vicidial.ws_url'),
            'domain' => config('vicidial.sip_domain'),
            'username' => $agent['sip_username'],
            'password' => $agent['sip_password'],
            'iceServers' => config('vicidial.ice_servers', []),
        ];
    }

    public function apiReady(): bool
    {
        try {
            $this->requireUrl(config('vicidial.api_url'), 'https', 'VICIDIAL_API_URL');
        } catch (RuntimeException $e) {
            return false;
        }
        return trim((string) config('vicidial.api_user')) !== ''
            && trim((string) config('vicidial.api_password')) !== '';
    }

    private function requireUrl(?string $url, string $scheme, string $setting): void
    {
        $parts = parse_url($url ?? '');
        if (! $parts || ($parts['scheme'] ?? '') !== $scheme || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            throw new RuntimeException("Configure {$setting} with a valid {$scheme} URL without credentials or query parameters.");
        }
    }

    public function command(int $userId, string $action, ?string $value = null): array
    {
        $agent = $this->agent($userId);
        $params = match ($action) {
            'connect' => ['function' => 'call_agent', 'value' => 'CALL'],
            'ready' => ['function' => 'external_pause', 'value' => 'RESUME'],
            'pause' => ['function' => 'external_pause', 'value' => 'PAUSE'],
            'hangup' => ['function' => 'external_hangup', 'value' => '1'],
            'disposition' => $this->disposition($value),
            'lead' => $this->leadParams($agent),
            default => throw ValidationException::withMessages(['action' => 'Unsupported VICIdial action.']),
        };
        $this->requireUrl(config('vicidial.api_url'), 'https', 'VICIDIAL_API_URL');
        if (! config('vicidial.api_user') || ! config('vicidial.api_password')) {
            throw new RuntimeException('VICIdial API credentials are not configured.');
        }

        $verify = config('vicidial.ca_bundle') ?: true;
        if (is_string($verify) && ! is_readable($verify)) {
            throw new RuntimeException('The VICIdial CA certificate file is not readable.');
        }

        try {
            // Do not retry state-changing commands or follow redirects with API credentials.
            $response = Http::asForm()->connectTimeout(5)->timeout(15)
                ->withOptions(['allow_redirects' => false, 'verify' => $verify])
                ->post(config('vicidial.api_url'), array_merge($params, [
                    'user' => config('vicidial.api_user'),
                    'pass' => config('vicidial.api_password'),
                    'agent_user' => $agent['agent_user'],
                    'source' => 'webportal',
                ]));
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new RuntimeException('VICIdial could not be reached. A command may already have been accepted; check the agent screen before retrying.');
        }

        $body = trim($response->body());
        if ($action === 'lead' && $response->successful() && str_starts_with($body, 'ERROR: no active lead found')) {
            return ['ok' => true, 'lead' => null];
        }
        if (! $response->successful() || ! str_starts_with($body, 'SUCCESS:')) {
            // Never forward upstream bodies: they can contain identifiers, credentials or HTML.
            $message = 'VICIdial rejected the request. Check API permissions and the active agent session.';
            if (str_contains(strtolower($body), 'not logged in') || str_contains(strtolower($body), 'no session data')) {
                $message = 'Log in to the VICIdial agent screen with your assigned phone and campaign, and keep that screen open.';
            }
            throw new RuntimeException($message);
        }

        if ($action === 'lead') {
            if (! preg_match('/^SUCCESS: st_get_agent_active_lead lead found - ([^\r\n]+)$/', $body, $match)) {
                throw new RuntimeException('VICIdial returned an unsupported lead response.');
            }
            $fields = explode('|', $match[1]);
            if (count($fields) < 3) {
                throw new RuntimeException('VICIdial returned an incomplete lead response.');
            }
            return ['ok' => true, 'lead' => ['phone' => $fields[1], 'id' => $fields[2]]];
        }

        return ['ok' => true, 'message' => 'Request accepted by VICIdial. Confirm the resulting state on the agent screen.'];
    }

    private function disposition(?string $value): array
    {
        if (! $value || ! in_array($value, config('vicidial.dispositions', []), true)) {
            throw ValidationException::withMessages(['value' => 'Select a configured disposition.']);
        }
        return ['function' => 'external_status', 'value' => $value];
    }

    private function leadParams(array $agent): array
    {
        if (empty($agent['crm_agent_id']) || empty($agent['crm_team_id'])) {
            throw ValidationException::withMessages(['action' => 'Lead lookup is not configured for this account.']);
        }
        return ['function' => 'st_get_agent_active_lead', 'value' => $agent['crm_agent_id'], 'vendor_id' => $agent['crm_team_id']];
    }
}
