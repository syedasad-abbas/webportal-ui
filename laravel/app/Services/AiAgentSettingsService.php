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

    /**
     * The expected questions with the answers the agent is allowed to give.
     * These are read by the AI configuration page and, through the settings
     * payload, by the live prompt builder on every inbound call.
     *
     * @return array<int, array<string, mixed>>
     */
    public function faqs(): array
    {
        $token = session('admin_token');

        $response = Http::acceptJson()
            ->withToken($token)
            ->get(config('services.backend.url').'/ai-agent/faqs');

        if (! $response->successful()) {
            return [];
        }

        return $response->json('faqs') ?? [];
    }

    /**
     * Replaces the whole ordered list in one request. The position in the
     * payload is the stored order, and anything the payload omits is removed,
     * so the browser never has to reconcile entry ids.
     *
     * @param  array<int, array<string, mixed>>  $faqs
     * @return array<int, array<string, mixed>>
     */
    public function replaceFaqs(array $faqs): array
    {
        $token = session('admin_token');

        $response = Http::acceptJson()
            ->withToken($token)
            ->put(config('services.backend.url').'/ai-agent/faqs', ['faqs' => $faqs]);

        if (! $response->successful()) {
            return [];
        }

        return $response->json('faqs') ?? [];
    }
}
