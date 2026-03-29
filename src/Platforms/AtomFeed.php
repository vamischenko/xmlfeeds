<?php

declare(strict_types=1);

namespace XmlFeeds\Platforms;

use XmlFeeds\Builder\FeedItem;
use XmlFeeds\Contracts\FeedFormat;
use XmlFeeds\Contracts\PlatformInterface;

/**
 * Atom 1.0 feed platform.
 *
 * Produces a standards-compliant Atom 1.0 document with the
 * `http://www.w3.org/2005/Atom` namespace. No additional namespace
 * extensions are used.
 *
 * Supported fields: `title`, `link`, `pubDate`, `description`, `author`,
 * `category`, `content`, `keywords`, `guid`.
 */
final class AtomFeed implements PlatformInterface
{
    /**
     * {@inheritDoc}
     */
    public function getFeedFormat(): FeedFormat
    {
        return FeedFormat::Atom;
    }

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
        return ['title', 'link', 'pubDate'];
    }

    /**
     * {@inheritDoc}
     */
    public function optionalFields(): array
    {
        return [
            'description',
            'author',
            'category',
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
        return dirname(__DIR__, 2).'/schemas/atom-1.0.xsd';
    }

    /**
     * {@inheritDoc}
     *
     * Whitelists keys to those allowed for Atom; no structural renaming.
     *
     * @return array<string, mixed>
     */
    public function transform(FeedItem $item): array
    {
        $data = $item->toArray();

        return array_intersect_key($data, array_flip(array_merge($this->requiredFields(), $this->optionalFields())));
    }
}
