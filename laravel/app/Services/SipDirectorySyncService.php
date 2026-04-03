<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SipDirectorySyncService
{
    public function __construct(
        private readonly Filesystem $files
    ) {
    }

    public function syncUser(User $user, ?string $previousUsername = null): bool
    {
        $user->load('sipCredential');

        $credential = $user->sipCredential;
        if (! $credential || ! $credential->sip_username || ! $credential->sip_password) {
            return $previousUsername ? $this->deleteByUsername($previousUsername) : false;
        }

        $username = trim((string) $credential->sip_username);
        $password = trim((string) $credential->sip_password);

        if ($username === '' || $password === '') {
            return $previousUsername ? $this->deleteByUsername($previousUsername) : false;
        }

        $this->ensureDirectoryExists();

        $path = $this->pathForUsername($username);
        $xml = $this->renderUserXml($user, $username, $password);

        $changed = ! $this->files->exists($path) || $this->files->get($path) !== $xml;
        if ($changed) {
            $this->files->put($path, $xml, true);
        }

        $removedPrevious = false;
        if ($previousUsername && $previousUsername !== $username) {
            $removedPrevious = $this->deleteByUsername($previousUsername, false);
        }

        if ($changed || $removedPrevious) {
            $this->reloadXml();
            return true;
        }

        return false;
    }

    public function deleteByUsername(?string $username, bool $reload = true): bool
    {
        $username = trim((string) $username);
        if ($username === '') {
            return false;
        }

        $path = $this->pathForUsername($username);
        if (! $this->files->exists($path)) {
            return false;
        }

        $this->files->delete($path);

        if ($reload) {
            $this->reloadXml();
        }

        return true;
    }

    public function deleteByUsernames(array $usernames): int
    {
        $deleted = 0;

        foreach (array_unique(array_filter(array_map(static fn ($username) => trim((string) $username), $usernames))) as $username) {
            if ($this->deleteByUsername($username, false)) {
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->reloadXml();
        }

        return $deleted;
    }

    public function syncAll(): int
    {
        $this->ensureDirectoryExists();

        $users = User::query()
            ->with('sipCredential')
            ->whereHas('sipCredential', function ($query) {
                $query->whereNotNull('sip_username')
                    ->where('sip_username', '!=', '')
                    ->whereNotNull('sip_password')
                    ->where('sip_password', '!=', '');
            })
            ->get();

        $changed = 0;

        foreach ($users as $user) {
            $credential = $user->sipCredential;
            if (! $credential) {
                continue;
            }

            $username = trim((string) $credential->sip_username);
            $password = trim((string) $credential->sip_password);

            if ($username === '' || $password === '') {
                continue;
            }

            $path = $this->pathForUsername($username);
            $xml = $this->renderUserXml($user, $username, $password);

            if (! $this->files->exists($path) || $this->files->get($path) !== $xml) {
                $this->files->put($path, $xml, true);
                $changed++;
            }
        }

        if ($changed > 0) {
            $this->reloadXml();
        }

        return $changed;
    }

    private function ensureDirectoryExists(): void
    {
        $path = $this->directoryPath();

        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0775, true);
        }

        if (! is_dir($path) || ! is_writable($path)) {
            throw new RuntimeException(sprintf('FreeSWITCH directory path is not writable: %s', $path));
        }
    }

    private function directoryPath(): string
    {
        $path = trim((string) config('services.freeswitch.directory_path', ''));

        if ($path === '') {
            throw new RuntimeException('FreeSWITCH directory path is not configured.');
        }

        return rtrim($path, '/');
    }

    private function pathForUsername(string $username): string
    {
        return $this->directoryPath().'/'.rawurlencode($username).'.xml';
    }

    private function reloadXml(): void
    {
        $backendUrl = rtrim((string) config('services.backend.url'), '/');
        $internalToken = (string) config('services.backend.internal_token');

        if ($backendUrl === '' || $internalToken === '') {
            throw new RuntimeException('Backend FreeSWITCH reload configuration is incomplete.');
        }

        $response = Http::timeout(5)
            ->withHeaders(['x-internal-token' => $internalToken])
            ->baseUrl($backendUrl)
            ->post('/admin/freeswitch/reloadxml');

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'FreeSWITCH reloadxml failed with status %d: %s',
                $response->status(),
                $response->body()
            ));
        }
    }

    private function renderUserXml(User $user, string $username, string $password): string
    {
        $callerIdName = trim((string) ($user->external_name ?: 'Extension '.$username));
        $xmlValues = [
            'username' => $this->xmlEscape($username),
            'password' => $this->xmlEscape($password),
            'vm_password' => $this->xmlEscape($username),
            'caller_id_name' => $this->xmlEscape($callerIdName),
        ];

        return <<<XML
<!-- Managed by Laravel SIP directory sync. -->
<include>
  <user id="{$xmlValues['username']}">
    <params>
      <param name="password" value="{$xmlValues['password']}"/>
      <param name="vm-password" value="{$xmlValues['vm_password']}"/>
    </params>
    <variables>
      <variable name="toll_allow" value="domestic,international,local"/>
      <variable name="accountcode" value="{$xmlValues['username']}"/>
      <variable name="user_context" value="default"/>
      <variable name="effective_caller_id_name" value="{$xmlValues['caller_id_name']}"/>
      <variable name="effective_caller_id_number" value="{$xmlValues['username']}"/>
      <variable name="outbound_caller_id_name" value="\$\${outbound_caller_name}"/>
      <variable name="outbound_caller_id_number" value="\$\${outbound_caller_id}"/>
      <variable name="callgroup" value="techsupport"/>
    </variables>
  </user>
</include>
XML;
    }

    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
