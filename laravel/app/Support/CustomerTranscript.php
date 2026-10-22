<?php

namespace App\Support;

final class CustomerTranscript
{
    public static function fromTaggedText(?string $text): string
    {
        $entries = json_decode($text ?? '', true);
        if (is_array($entries)) {
            $lines = [];
            foreach ($entries as $entry) {
                if (is_array($entry) && ($entry['role'] ?? null) === 'caller' && is_string($entry['text'] ?? null)) {
                    $lines[] = $entry['text'];
                }
            }
            return implode("\n", $lines);
        }
        $caller = false;
        $lines = [];
        foreach (preg_split('/\R/u', $text ?? '') ?: [] as $line) {
            if (preg_match('/^(caller|agent|assistant|system|user):\s*(.*)$/i', $line, $match)) {
                $caller = strtolower($match[1]) === 'caller';
                $line = $match[2];
            }
            // Unlabelled legacy transcripts have no reliable speaker attribution.
            if ($caller && trim($line) !== '') {
                $lines[] = $line;
            }
        }
        return implode("\n", $lines);
    }
}
