<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\AiAgentSettingsService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AiAgentSettingsServiceTest extends TestCase
{
    #[Test]
    public function replace_faqs_sends_the_trusted_internal_token_with_the_user_token(): void
    {
        session()->put('admin_token', 'user-token');
        config()->set('services.backend.url', 'http://backend:4000');
        config()->set('services.backend.internal_token', 'internal-token');

        $faqs = [[
            'question' => 'enlist doctors names',
            'answer' => 'Dr. Ali Ammar',
            'isEnabled' => true,
        ]];

        Http::fake([
            'http://backend:4000/ai-agent/faqs' => Http::response([
                'ok' => true,
                'faqs' => $faqs,
            ]),
        ]);

        $this->assertSame($faqs, app(AiAgentSettingsService::class)->replaceFaqs($faqs));

        Http::assertSent(function (Request $request) use ($faqs): bool {
            return $request->hasHeader('Authorization', 'Bearer user-token')
                && $request->hasHeader('x-internal-token', 'internal-token')
                && $request->method() === 'PUT'
                && $request->url() === 'http://backend:4000/ai-agent/faqs'
                && $request->data() === ['faqs' => $faqs];
        });
    }

    #[Test]
    public function failed_empty_save_is_distinct_from_successful_deletion(): void
    {
        Http::fakeSequence()
            ->push(['message' => 'Invalid CSRF token'], 403)
            ->push(['ok' => true, 'faqs' => []]);

        $service = app(AiAgentSettingsService::class);

        $this->assertNull($service->replaceFaqs([]));
        $this->assertSame([], $service->replaceFaqs([]));
    }

    #[Test]
    public function malformed_success_response_is_not_treated_as_a_saved_list(): void
    {
        Http::fakeSequence()->push(['ok' => true])->push(['faqs' => 'invalid']);

        $service = app(AiAgentSettingsService::class);

        $this->assertNull($service->replaceFaqs([]));
        $this->assertNull($service->replaceFaqs([]));
    }
}
