<?php

declare(strict_types=1);

namespace XmlFeeds\Generator;

/**
 * Heuristic helper that decides whether a string value should be wrapped in
 * a CDATA section when emitted as an XML element body.
 *
 * The rule is deliberately simple: any non-empty string that contains both
 * `<` and `>` is treated as markup and wrapped in CDATA. This covers HTML
 * fragments without requiring a full HTML parse.
 *
 * Certain field keys (e.g. `content`, `content:encoded`, `yandex:full-text`)
 * are always wrapped in CDATA regardless of their content — that list lives
 * in {@see XmlGenerator::CDATA_KEYS}.
 */
final class CdataWrapper
{
    /**
     * Return `true` when the value looks like markup and should be CDATA-wrapped.
     *
     * Does **not** need to be called for keys already listed in
     * `XmlGenerator::CDATA_KEYS` — those are wrapped unconditionally.
     */
    public static function prefersCdata(string $value): bool
    {
        $t = \trim($value);

        return $t !== '' && \str_contains($t, '<') && \str_contains($t, '>');
    }
}
