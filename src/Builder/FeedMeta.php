<?php

declare(strict_types=1);

namespace XmlFeeds\Builder;

use DateTimeInterface;

/**
 * Immutable DTO that holds the channel-level metadata for a feed.
 *
 * Created by {@see FeedBuilder} from the values set via `title()`, `link()`,
 * `description()`, `language()`, and `lastBuildDate()`, then passed to the
 * generator as a single value object.
 */
final readonly class FeedMeta
{
    /**
     * @param string                  $title         Channel title (RSS `<title>` / Atom `<title>`).
     * @param string                  $link          Channel URL (RSS `<link>` / Atom `<link href="…">`).
     * @param string                  $description   Channel description (RSS `<description>` / Atom `<subtitle>`).
     * @param string                  $language      BCP 47 language tag, e.g. `'ru'`, `'en'` (default: `'ru'`).
     * @param DateTimeInterface|null  $lastBuildDate Optional last-modified timestamp.
     */
    public function __construct(
        public string $title,
        public string $link,
        public string $description,
        public string $language = 'ru',
        public ?DateTimeInterface $lastBuildDate = null,
    ) {
    }
}
