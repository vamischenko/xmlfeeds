<?php

declare(strict_types=1);

namespace XmlFeeds\Contracts;

/**
 * Represents a single item in a feed.
 *
 * The built-in implementation is {@see \XmlFeeds\Builder\FeedItem}.
 * Implement this interface when you need a custom item representation
 * (e.g. a domain object that already has the right field names).
 */
interface FeedItemInterface
{
    /**
     * Return all item fields as an associative array.
     *
     * Keys are the generic field names understood by the mapper
     * (e.g. `'title'`, `'link'`, `'pubDate'`, `'content'`).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
