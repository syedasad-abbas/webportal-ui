<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Concerns;

trait ResolvesAiAgentLabels
{
    /**
     * Resolve a label for the AI agent, preferring the administrator override
     * stored in Settings and falling back to the shipped default when it is
     * unset or blank. Shared by the dialer page and the AI agent settings page
     * so both always present the same configured title.
     */
    protected function aiAgentBoxText(string $settingKey, string $defaultKey): string
    {
        $configured = config('settings.'.$settingKey);

        if (is_string($configured)) {
            $configured = trim($configured);

            if ($configured !== '') {
                return $configured;
            }
        }

        return (string) config('aiagent.'.$defaultKey);
    }
}