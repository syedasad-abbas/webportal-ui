<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/** Change this adapter's endpoint/field mapping when replacing the test CRM. */
class CrmApiClient
{
    private const MAX_RETRIES = 3;
    private const RETRYABLE_STATUS = [429, 502, 503, 504];

    public function request(string $method, string $resource, array $data = []): array
    {
        if (!preg_match('~^(customers(?:/[1-9][0-9]*)?|reports/summary)$~', $resource)
            || !in_array($method, ['GET', 'POST', 'PUT', 'DELETE'], true)) {
            throw new \InvalidArgumentException('Unsupported CRM operation');
        }
        $connection = app(CrmWorkflow::class)->connection();
        $base = rtrim((string) $connection['url'], '/');
        $parts = parse_url($base);
        $local = in_array($parts['host'] ?? '', ['localhost', '127.0.0.1', '::1'], true);
        abort_unless($base && $connection['token'] && !isset($parts['user']) && !isset($parts['pass'])
            && !isset($parts['query']) && !isset($parts['fragment'])
            && (($parts['scheme'] ?? '') === 'https' || ($local && ($parts['scheme'] ?? '') === 'http')), 503, 'Configure a trusted CRM API URL and token.');

        $lastError = null;
        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $response = Http::acceptJson()->withToken($connection['token'])->connectTimeout(2)->timeout(8)
                    ->withoutRedirecting()->send($method, $base.'/'.$resource, $method === 'GET' ? ['query' => $data] : ['json' => $data]);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $lastError = $e;
                if ($method === 'GET' && $attempt < self::MAX_RETRIES) {
                    usleep(self::backoffMs($attempt) * 1000);
                    continue;
                }
                abort(503, 'CRM is unavailable. Try again.');
            }

            if ($response->status() === 422) {
                $errors = $response->json('errors');
                throw \Illuminate\Validation\ValidationException::withMessages(is_array($errors) ? $errors : ['crm' => 'CRM rejected the submitted values.']);
            }
            if ($response->status() === 404) abort(404, 'CRM record not found.');
            if ($response->status() === 204) return [];
            if ($response->successful()) {
                $body = $response->json();
                abort_unless(is_array($body) && array_key_exists('data', $body) && is_array($body['data']), 502, 'Invalid CRM response.');
                return $body;
            }
            // Retryable server errors
            if (in_array($response->status(), self::RETRYABLE_STATUS, true) && $method === 'GET' && $attempt < self::MAX_RETRIES) {
                $lastError = new \RuntimeException('CRM HTTP ' . $response->status());
                usleep(self::backoffMs($attempt) * 1000);
                continue;
            }
            abort_unless($response->successful(), 502, 'CRM request failed. Check the connection and credentials.');
        }
        throw $lastError;
    }

    private static function backoffMs(int $attempt): int
    {
        // Exponential backoff: 200ms, 400ms, 800ms
        return 200 * (2 ** ($attempt - 1));
    }
}
