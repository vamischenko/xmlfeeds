<?php

declare(strict_types=1);

namespace XmlFeeds\Platforms;

use XmlFeeds\Builder\FeedItem;

/**
 * RSS feed for Telegram channels / Telegram RSS bots.
 *
 * Telegram has no official XML feed format, but a number of bots and services
 * (e.g. @rssnewsbot, RSSHub) consume standard RSS 2.0 and publish entries to
 * a channel. This platform generates a compatible feed:
 *
 *  - Full HTML content is delivered via `content:encoded` (CDATA).
 *  - The item title is truncated to {@see TITLE_MAX_LENGTH} characters to
 *    respect Telegram's caption limit.
 *  - Media attachments are supported via `enclosure`.
 *
 * Namespace extensions:
 *  - `content` — `http://purl.org/rss/1.0/modules/content/`
 *
 * Required fields: `title`, `link`.
 *
 * Optional fields: `description`, `pubDate`, `author`, `category`,
 * `enclosure`, `content`, `keywords`, `guid`.
 */
final class TelegramChannel extends AbstractContentRssPlatform
{
    /**
     * Maximum title length (Telegram caption limit).
     */
    private const TITLE_MAX_LENGTH = 255;

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

    /**
     * {@inheritDoc}
     *
     * Truncates `title` to {@see TITLE_MAX_LENGTH} characters, then applies
     * the parent {@see AbstractContentRssPlatform::transform()} mapping.
     *
     * @return array<string, mixed>
     */
    public function transform(FeedItem $item): array
    {
        $out = parent::transform($item);

        if (isset($out['title']) && \is_string($out['title'])) {
            $out['title'] = \mb_substr($out['title'], 0, self::TITLE_MAX_LENGTH);
        }

        return $out;
    }
}
