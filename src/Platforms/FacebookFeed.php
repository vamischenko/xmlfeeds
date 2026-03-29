<?php

declare(strict_types=1);

namespace XmlFeeds\Platforms;

/**
 * Facebook Instant Articles feed platform (RSS 2.0 + `content:encoded`).
 *
 * Facebook supports two RSS scenarios:
 *  1. **Instant Articles** — the official Meta format for publishing content
 *     directly in Facebook. Full HTML is delivered via `content:encoded`.
 *     See: https://developers.facebook.com/docs/instant-articles/reference
 *  2. **Plain RSS 2.0** — for third-party autopublishing tools
 *     (Buffer, Hootsuite, dlvr.it, etc.).
 *
 * This class targets scenario 1 (Instant Articles). The `content` field is
 * **required** because Instant Articles needs the complete article HTML.
 *
 * Namespace extensions:
 *  - `content` — `http://purl.org/rss/1.0/modules/content/`
 *
 * Required fields: `title`, `link`, `content`.
 *
 * Optional fields: `description`, `pubDate`, `author`, `category`,
 * `enclosure`, `keywords`, `guid`.
 */
final class FacebookFeed extends AbstractContentRssPlatform
{
    /**
     * {@inheritDoc}
     */
    public function namespace(): array
    {
        return [
            'content' => 'http://purl.org/rss/1.0/modules/content/',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function requiredFields(): array
    {
        return ['title', 'link', 'content'];
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
}
