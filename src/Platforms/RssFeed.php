<?php

declare(strict_types=1);

namespace XmlFeeds\Platforms;

use XmlFeeds\Builder\FeedItem;

/**
 * Plain RSS 2.0 feed platform, with no namespace extensions.
 *
 * Use this as a generic, widely-compatible feed. For platform-specific
 * extensions (Yandex, Google News, etc.) use the dedicated platform classes.
 *
 * Required fields: `title`, `link`.
 *
 * Optional fields: `description`, `pubDate`, `author`, `category`,
 * `enclosure`, `content`, `keywords`, `guid`.
 *
 * @see https://www.rssboard.org/rss-specification
 */
final class RssFeed extends AbstractRssPlatform
{
    /**
     * {@inheritDoc}
     */
    public function namespace(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function requiredFields(): array
    {
        return ['title', 'link'];
    }

    /**
     * {@inheritDoc}
     */
    public function optionalFields(): array
    {
        return [
            'description',
            'pubDate',
            'author',
            'category',
            'enclosure',
            'content',
            'keywords',
            'guid',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function schemaPath(): string
    {
        return \dirname(__DIR__, 2) . '/schemas/rss-2.0.xsd';
    }

    /**
     * {@inheritDoc}
     *
     * Returns only the fields declared by this platform; no field renaming.
     *
     * @return array<string, mixed>
     */
    public function transform(FeedItem $item): array
    {
        $data    = $this->baseTransform($item);
        $allowed = \array_merge($this->requiredFields(), $this->optionalFields());

        return $this->filterKeys($data, $allowed);
    }
}
