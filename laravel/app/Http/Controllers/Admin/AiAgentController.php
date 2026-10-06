<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesAiAgentLabels;
use App\Http\Controllers\Controller;
use App\Services\AiAgentSettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAgentController extends Controller
{
    use ResolvesAiAgentLabels;

    /**
     * Full page configuration for the AI agent. This replaced the floating card
     * that used to sit over the dialer, which was too narrow for the duty,
     * greeting, and objective fields.
     */
    public function index(Request $request, AiAgentSettingsService $settings): View
    {
        $this->assertAiAgentPermission($request);

        $aiAgentSettings = $settings->get();

        // The expected questions are requested separately rather than read out of
        // the settings payload so the page still renders them when the settings
        // call fails: the admin can prepare the wording before the backend is
        // reachable.
        $aiAgentFaqs = $settings->faqs();

        return view('backend.pages.dialer.ai-agent', [
            'aiAgentSettings' => $aiAgentSettings,
            'aiAgentFaqs' => $aiAgentFaqs,
            'aiAgentBoxTitle' => $this->aiAgentBoxText('ai_box_title', 'box_title'),
            'aiAgentBoxSubtitle' => $this->aiAgentBoxText('ai_box_subtitle', 'box_subtitle'),
        ]);
    }

    /**
     * The expected questions the agent must answer from. Saved as one ordered
     * list so a caller who phrases a question differently still lands on the
     * stored answer, and so an answer can be corrected in one place instead of
     * being reworded on every call.
     */
    public function updateFaqs(Request $request, AiAgentSettingsService $settings): JsonResponse
    {
        $this->assertAiAgentPermission($request);

        $data = $request->validate([
            'faqs' => ['present', 'array', 'max:100'],
            // Free text on purpose: the questions are written the way a caller
            // would ask them and the answers may be a line or a short script, so
            // only length is bounded. A row that is still half typed is allowed
            // through validation on purpose: it is dropped below rather than
            // failing the whole save, so one unfinished row cannot block the
            // questions the administrator did finish.
            'faqs.*.question' => ['nullable', 'string', 'max:500'],
            'faqs.*.answer' => ['nullable', 'string', 'max:2000'],
            'faqs.*.isEnabled' => ['sometimes', 'boolean'],
        ]);

        $faqs = collect($data['faqs'] ?? [])
            ->map(fn ($faq): array => [
                'question' => trim((string) ($faq['question'] ?? '')),
                'answer' => trim((string) ($faq['answer'] ?? '')),
                'isEnabled' => (bool) ($faq['isEnabled'] ?? true),
            ])
            // A half-filled row would reach the prompt as a blank line the model
            // could try to read out, so it is dropped rather than stored.
            ->filter(fn (array $faq): bool => $faq['question'] !== '' && $faq['answer'] !== '')
            ->values()
            ->all();

        $saved = $settings->replaceFaqs($faqs);

        if ($saved === null || ($saved === [] && $faqs !== [])) {
            return response()->json([
                'ok' => false,
                'message' => __('The backend did not accept the expected questions.'),
            ], 502);
        }

        return response()->json(['ok' => true, 'faqs' => $saved]);
    }

    /**
     * The same permission the backend enforces on the AI agent API, so a user
     * never sees a configuration page they cannot actually save.
     */
    protected function assertAiAgentPermission(Request $request): void
    {
        if (! $request->user()
            || (! $request->user()->can('ai_agent.configure') && ! $request->user()->can('dialer.create_call'))) {
            abort(403, __('You do not have permission to configure the AI agent.'));
        }
    }
}
