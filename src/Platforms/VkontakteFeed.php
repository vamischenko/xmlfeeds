<?php

declare(strict_types=1);

namespace XmlFeeds\Platforms;

/**
 * RSS feed for VKontakte (VK) / SMM autopublishing services.
 *
 * VK supports content import via RSS 2.0 in the "Articles" section and
 * through third-party autopublishing tools (SMMplanner, Postmypost, etc.).
 * The feed complies with RSS 2.0; full text is passed via `content:encoded`,
 * images via `enclosure`.
 *
 * Namespace extensions:
 *  - `content` — `http://purl.org/rss/1.0/modules/content/`
 *
 * Required fields: `title`, `link`.
 *
 * Optional fields: `description`, `pubDate`, `author`, `category`,
 * `enclosure`, `content`, `keywords`, `guid`.
 */
final class VkontakteFeed extends AbstractContentRssPlatform
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
}
