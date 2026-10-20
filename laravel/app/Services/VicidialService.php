<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class VicidialService
{
    public function configuredAgent(int $userId): ?array
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('vicidial_credentials')) {
            $record = \App\Models\VicidialCredential::where('user_id', $userId)->first();
            if ($record) {
                return array_merge($record->toArray(), ['sip_password' => $record->sip_password,
                    'api_username' => $record->api_username, 'api_password' => $record->api_password]);
            }
        }
        return config('vicidial.enabled') ? (config('vicidial.agents', [])[$userId] ?? null) : null;
    }

    public function agent(int $userId): array
    {
        abort_unless(config('vicidial.enabled'), 404);
        $agent = $this->configuredAgent($userId);
        abort_unless(is_array($agent) && ! empty($agent['agent_user']), 403, 'No VICIdial agent is assigned to your account.');
        return $agent;
    }

    public function browserConfig(int $userId): array
    {
        $agent = $this->agent($userId);
        $wsUrl = $agent['ws_url'] ?? config('vicidial.ws_url');
        $domain = $agent['sip_domain'] ?? config('vicidial.sip_domain');
        $this->requireUrl($wsUrl, 'wss', 'VICIDIAL_WSS_URL');
        foreach ([$domain, $agent['sip_username'] ?? '', $agent['sip_password'] ?? ''] as $value) {
            if (! is_string($value) || trim($value) === '') {
                throw new RuntimeException('VICIdial configuration is incomplete. Ask your administrator to configure your phone account.');
            }
        }

        return [
            'wsUrl' => $wsUrl,
            'domain' => $domain,
            'username' => $agent['sip_username'],
            'password' => $agent['sip_password'],
            'iceServers' => config('vicidial.ice_servers', []),
        ];
    }

    public function apiReady(?int $userId = null): bool
    {
        try {
            $this->requireUrl(config('vicidial.api_url'), 'https', 'VICIDIAL_API_URL');
        } catch (RuntimeException $e) {
            return false;
        }
        $credentials = $this->apiCredentials($userId === null ? [] : $this->agent($userId));
        return trim((string) $credentials['username']) !== '' && trim((string) $credentials['password']) !== '';
    }

    private function apiCredentials(array $agent): array
    {
        // Never combine one user's password with the shared API username, or
        // substitute the SIP registration password for an API credential.
        if (! empty($agent['api_username']) || ! empty($agent['api_password'])) {
            return ['username' => $agent['api_username'] ?? '', 'password' => $agent['api_password'] ?? ''];
        }
        return ['username' => config('vicidial.api_user'), 'password' => config('vicidial.api_password')];
    }

    private function requireUrl(?string $url, string $scheme, string $setting): void
    {
        $parts = parse_url($url ?? '');
        if (! $parts || ($parts['scheme'] ?? '') !== $scheme || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            throw new RuntimeException("Configure {$setting} with a valid {$scheme} URL without credentials or query parameters.");
        }
    }

    public function command(int $userId, string $action, ?string $value = null, array $options = []): array
    {
        $agent = $this->agent($userId);
        $params = match ($action) {
            'connect' => ['function' => 'call_agent', 'value' => 'CALL'],
            'ready' => ['function' => 'external_pause', 'value' => 'RESUME'],
            'pause' => ['function' => 'external_pause', 'value' => 'PAUSE'],
            'hangup' => ['function' => 'external_hangup', 'value' => '1'],
            'disposition' => $this->disposition($value),
            'lead' => $this->leadParams($agent),
            'dial' => $this->dialParams($value, $options['phone_code'] ?? null),
            'park' => ['function' => 'park_call', 'value' => 'PARK_CUSTOMER'],
            'unpark' => ['function' => 'park_call', 'value' => 'GRAB_CUSTOMER'],
            'dtmf' => $this->dtmfParams($value),
            'conference', 'transfer' => $this->conferenceParams($action, $value),
            'end_conference' => ['function' => 'transfer_conference', 'value' => 'HANGUP_XFER'],
            'logout' => ['function' => 'logout', 'value' => 'LOGOUT'],
            'queue' => ['function' => 'calls_in_queue_count', 'value' => 'DISPLAY'],
            default => throw ValidationException::withMessages(['action' => 'Unsupported VICIdial action.']),
        };
        $this->requireUrl(config('vicidial.api_url'), 'https', 'VICIDIAL_API_URL');
        $credentials = $this->apiCredentials($agent);
        if (! $credentials['username'] || ! $credentials['password']) {
            throw new RuntimeException('VICIdial API credentials are not configured for this account. Set them in Users → Edit.');
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
                    'user' => $credentials['username'],
                    'pass' => $credentials['password'],
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
            if (str_contains(strtolower($body), 'invalid username/password')) {
                $message = 'VICIdial rejected the API credentials. Ask your administrator to verify the API username and password, that the account is active, and that API access is enabled.';
            } elseif (str_contains(strtolower($body), 'not logged in') || str_contains(strtolower($body), 'no session data')) {
                $message = 'Log in to the VICIdial agent screen with your assigned phone and campaign, and keep that screen open.';
            }
            throw new RuntimeException($message);
        }

        if ($action === 'queue') {
            if (! preg_match('/^SUCCESS: calls_in_queue_count - ([0-9]+)$/D', $body, $match)) {
                throw new RuntimeException('VICIdial returned an unsupported queue response.');
            }
            return ['ok' => true, 'queuedCalls' => (int) $match[1], 'message' => 'Calls waiting: '.$match[1]];
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

    public function conferenceTargets(): array
    {
        // Browser sends a configured name, never an arbitrary dialplan destination.
        $targets = config('vicidial.conference_targets', []);
        if (! is_array($targets)) {
            return [];
        }
        return array_filter($targets, fn ($number, $name) => is_string($name)
            && preg_match('/^[a-zA-Z][a-zA-Z0-9_-]{0,39}$/D', $name)
            && is_string($number) && preg_match('/^[0-9]{1,25}$/D', $number), ARRAY_FILTER_USE_BOTH);
    }

    private function dialParams(?string $number, ?string $phoneCode): array
    {
        if (! preg_match('/^[0-9]{3,20}$/D', $number ?? '') || ! preg_match('/^[0-9]{1,4}$/D', $phoneCode ?? '')) {
            throw ValidationException::withMessages(['value' => 'Enter a phone number and country code using digits only.']);
        }
        return ['function' => 'external_dial', 'value' => $number, 'phone_code' => $phoneCode,
            'search' => 'YES', 'preview' => 'NO', 'focus' => 'NO'];
    }

    private function dtmfParams(?string $digits): array
    {
        if (! preg_match('/^[0-9*#]{1,20}$/D', $digits ?? '')) {
            throw ValidationException::withMessages(['value' => 'Enter up to 20 digits, * or #.']);
        }
        return ['function' => 'send_dtmf', 'value' => strtr($digits, ['*' => 'S', '#' => 'P'])];
    }

    private function conferenceParams(string $action, ?string $target): array
    {
        $number = $this->conferenceTargets()[$target ?? ''] ?? null;
        if (! $number) {
            throw ValidationException::withMessages(['value' => 'Select a configured transfer or conference target.']);
        }
        return ['function' => 'transfer_conference',
            'value' => $action === 'conference' ? 'DIAL_WITH_CUSTOMER' : 'BLIND_TRANSFER',
            'phone_number' => $number, 'dial_override' => 'YES'];
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
