<?php

declare(strict_types=1);

namespace XmlFeeds\Generator;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Converts date values to the string formats required by RSS 2.0 and Atom 1.0.
 *
 * Accepts both {@see DateTimeInterface} objects and date strings parseable by
 * {@see DateTimeImmutable}. An invalid string raises an {@see \InvalidArgumentException}
     * with the offending value included in the message (wraps the underlying {@see \Exception}).
 */
final class XmlDate
{
    /**
     * Format a date as RFC 2822, as required by RSS 2.0 `<pubDate>` and `<lastBuildDate>`.
     *
     * Example output: `Sun, 29 Mar 2026 12:00:00 +0000`
     *
     * @throws \InvalidArgumentException When `$date` is a string that cannot be parsed.
     */
    public static function toRfc2822(DateTimeInterface|string $date): string
    {
        return self::immutable($date)->format(DateTimeInterface::RFC2822);
    }

    /**
     * Format a date as ISO 8601 / Atom, as required by Atom 1.0 `<updated>` and `<published>`.
     *
     * Example output: `2026-03-29T12:00:00+00:00`
     *
     * @throws \InvalidArgumentException When `$date` is a string that cannot be parsed.
     */
    public static function toAtom(DateTimeInterface|string $date): string
    {
        return self::immutable($date)->format(DateTimeInterface::ATOM);
    }

    /**
     * Normalise any date value to a {@see DateTimeImmutable}.
     *
     * @throws \InvalidArgumentException When a string value cannot be parsed.
     */
    private static function immutable(DateTimeInterface|string $date): DateTimeImmutable
    {
        if ($date instanceof DateTimeImmutable) {
            return $date;
        }

        if ($date instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($date);
        }

        try {
            return new DateTimeImmutable($date);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                \sprintf('Cannot parse date string "%s": %s', $date, $e->getMessage()),
                0,
                $e,
            );
        }
    }
}
