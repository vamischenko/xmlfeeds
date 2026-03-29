<?php

declare(strict_types=1);

namespace XmlFeeds\Platforms;

use XmlFeeds\Builder\FeedItem;

/**
 * Yandex Zen (Dzen) feed platform (RSS 2.0 + `media:*` / `content:*`).
 *
 * Generates an RSS 2.0 feed for Yandex Zen. Images are delivered via
 * `media:content` and `media:thumbnail`; full article text via
 * `content:encoded`. An `enclosure` field is automatically promoted to
 * `media:content` and `media:thumbnail` when those fields are absent.
 *
 * Namespace extensions:
 *  - `media`   — `http://search.yahoo.com/mrss/`
 *  - `content` — `http://purl.org/rss/1.0/modules/content/`
 *
 * Required fields: `title`, `link`, `pubDate`.
 *
 * Optional fields: `description`, `author`, `category`, `enclosure`,
 * `content`, `keywords`, `guid`, `media:content`, `media:thumbnail`.
 *
 * @see https://dzen.ru/help/news/rss.html
 */
final class YandexZen extends AbstractRssPlatform
{
    /**
     * {@inheritDoc}
     */
    public function namespace(): array
    {
        return [
            'media'   => 'http://search.yahoo.com/mrss/',
            'content' => 'http://purl.org/rss/1.0/modules/content/',
        ];
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
            'enclosure',
            'content',
            'keywords',
            'guid',
            'media:content',
            'media:thumbnail',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function schemaPath(): string
    {
        return \dirname(__DIR__, 2) . '/schemas/yandex-zen.xsd';
    }

    /**
     * {@inheritDoc}
     *
     * - `enclosure` → `media:content` + `media:thumbnail` (when not already set)
     * - `content`   → `content:encoded` (CDATA)
     *
     * @return array<string, mixed>
     */
    public function transform(FeedItem $item): array
    {
        $data = $this->baseTransform($item);
        $out  = $this->filterKeys($data, \array_merge($this->requiredFields(), $this->optionalFields()));

        if (!isset($out['media:content']) && isset($data['enclosure'])) {
            $enc = $data['enclosure'];
            if (\is_array($enc) && isset($enc['url'])) {
                $out['media:content'] = [
                    'url'    => (string) $enc['url'],
                    'type'   => isset($enc['type']) ? (string) $enc['type'] : 'image/jpeg',
                    'medium' => 'image',
                ];
            }
        }

        if (!isset($out['media:thumbnail']) && isset($data['enclosure'])) {
            $enc = $data['enclosure'];
            if (\is_array($enc) && isset($enc['url'])) {
                $out['media:thumbnail'] = [
                    'url' => (string) $enc['url'],
                ];
            }
        }

        if (isset($out['content'])) {
            $out['content:encoded'] = (string) $out['content'];
            unset($out['content']);
        }

        return $out;
    }
}
