<?php

declare(strict_types=1);

namespace XmlFeeds\Platforms;

use XmlFeeds\Builder\FeedItem;

/**
 * Yandex News feed platform (RSS 2.0 + `yandex:*` / `content:*`).
 *
 * Generates an RSS 2.0 feed compliant with Yandex News requirements.
 * Full article text is delivered via `yandex:full-text` (CDATA).
 * Source and genre metadata are mapped to `yandex:source` and `yandex:genre`.
 *
 * Namespace extensions:
 *  - `yandex`   — `http://news.yandex.ru`
 *  - `content`  — `http://purl.org/rss/1.0/modules/content/`
 *
 * Required fields: `title`, `link`, `pubDate`.
 *
 * Optional fields: `description`, `author`, `category`, `enclosure`,
 * `content`, `keywords`, `guid`, `source`, `genre`.
 *
 * @see https://yandex.ru/support/news/feed.html
 */
final class YandexNews extends AbstractRssPlatform
{
    /**
     * {@inheritDoc}
     */
    public function namespace(): array
    {
        return [
            'yandex'  => 'http://news.yandex.ru',
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
            'source',
            'genre',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function schemaPath(): string
    {
        return \dirname(__DIR__, 2) . '/schemas/yandex-news.xsd';
    }

    /**
     * {@inheritDoc}
     *
     * Maps generic fields to Yandex News-specific names:
     *
     * - `content` → `yandex:full-text` (CDATA)
     * - `source`  → `yandex:source`
     * - `genre`   → `yandex:genre`
     *
     * @return array<string, mixed>
     */
    public function transform(FeedItem $item): array
    {
        $data = $this->baseTransform($item);
        $out  = $this->filterKeys($data, \array_merge($this->requiredFields(), $this->optionalFields()));

        if (isset($out['content'])) {
            $out['yandex:full-text'] = (string) $out['content'];
            unset($out['content']);
        }

        if (isset($data['source'])) {
            $out['yandex:source'] = (string) $data['source'];
        }
        if (isset($data['genre'])) {
            $out['yandex:genre'] = (string) $data['genre'];
        }

        return $out;
    }
}
