<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/** Change this adapter's endpoint/field mapping when replacing the test CRM. */
class CrmApiClient
{
    public function request(string $method, string $resource, array $data = []): array
    {
        if (!preg_match('~^(customers(?:/[1-9][0-9]*)?|reports/summary)$~', $resource)
            || !in_array($method, ['GET', 'POST', 'PUT', 'DELETE'], true)) {
            throw new \InvalidArgumentException('Unsupported CRM operation');
        }
        $base = rtrim((string) config('crm.base_url'), '/');
        $parts = parse_url($base);
        $local = in_array($parts['host'] ?? '', ['localhost', '127.0.0.1', '::1'], true);
        abort_unless($base && config('crm.token') && !isset($parts['user']) && !isset($parts['pass'])
            && !isset($parts['query']) && !isset($parts['fragment'])
            && (($parts['scheme'] ?? '') === 'https' || ($local && ($parts['scheme'] ?? '') === 'http')), 503, 'Configure a trusted CRM API URL and token.');
        try {
            $response = Http::acceptJson()->withToken(config('crm.token'))->connectTimeout(2)->timeout(8)
                ->withoutRedirecting()->send($method, $base.'/'.$resource, $method === 'GET' ? ['query' => $data] : ['json' => $data]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            abort(503, 'CRM is unavailable. Try again.');
        }
        if ($response->status() === 422) {
            $errors = $response->json('errors');
            throw \Illuminate\Validation\ValidationException::withMessages(is_array($errors) ? $errors : ['crm' => 'CRM rejected the submitted values.']);
        }
        if ($response->status() === 404) abort(404, 'CRM record not found.');
        abort_unless($response->successful(), 502, 'CRM request failed. Check the connection and credentials.');
        if ($response->status() === 204) return [];
        $body = $response->json();
        abort_unless(is_array($body) && array_key_exists('data', $body) && is_array($body['data']), 502, 'Invalid CRM response.');
        return $body;
    }
}
