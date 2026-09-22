<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Http;

class AiAgentSettingsService
{
    public function __construct(protected HttpClient $http)
    {
    }

    public function get(): ?array
    {
        $token = session('admin_token');

        $response = Http::acceptJson()
            ->withToken($token)
            ->get(config('services.backend.url').'/ai-agent');

        if (! $response->successful()) {
            return null;
        }

        return $response->json('settings');
    }

    public function update(array $settings, ?int $userId = null): ?array
    {
        $token = session('admin_token');

        $response = Http::acceptJson()
            ->withToken($token)
            ->put(config('services.backend.url').'/ai-agent', $settings);

        if (! $response->successful()) {
            return null;
        }

        return $response->json('settings');
    }
}
