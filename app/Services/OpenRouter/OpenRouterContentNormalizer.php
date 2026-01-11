<?php

namespace App\Services\OpenRouter;

final class OpenRouterContentNormalizer
{
    public static function toStringOrNull(mixed $content): ?string
    {
        // OpenRouter は content を string / parts(array) の両方で返しうるため、呼び出し側を単純化する。
        if (is_string($content)) {
            return $content;
        }

        if (!is_array($content)) {
            return null;
        }

        $parts = [];

        foreach ($content as $part) {
            if (!is_array($part)) {
                continue;
            }

            if (($part['type'] ?? null) !== 'text') {
                // 画像/ツール出力など text 以外は、このアプリでは扱わない。
                continue;
            }

            $text = $part['text'] ?? null;
            if (!is_string($text)) {
                continue;
            }

            $parts[] = $text;
        }

        if ($parts === []) {
            return null;
        }

        // parts は順序が意味を持つので、そのまま連結する。
        return implode('', $parts);
    }
}
