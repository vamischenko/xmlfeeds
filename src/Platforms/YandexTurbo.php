<?php

declare(strict_types=1);

namespace XmlFeeds\Platforms;

use XmlFeeds\Builder\FeedItem;

/**
 * Yandex Turbo Pages feed platform (RSS 2.0 + `turbo:*` / `content:*`).
 *
 * Generates RSS 2.0 with the Yandex Turbo extension.
 * The full HTML content of a turbo page must be supplied via the
 * `turboContent` field (preferred) or the generic `content` field.
 * `description` is kept as-is and is **not** silently promoted to
 * `content:encoded` — pass `content` or `turboContent` explicitly for that.
 *
 * Namespace extensions:
 *  - `turbo` — `http://turbo.yandex.ru`
 *  - `content` — `http://purl.org/rss/1.0/modules/content/`
 *
 * Required fields: `title`, `link`, `pubDate`.
 *
 * Optional fields: `description`, `author`, `category`, `enclosure`,
 * `content`, `turboContent`, `keywords`, `guid`.
 *
 * @see https://yandex.ru/dev/turbo/doc/rss/elements/
 */
final class YandexTurbo extends AbstractRssPlatform
{
    /**
     * {@inheritDoc}
     */
    public function namespace(): array
    {
        return [
            'turbo'   => 'http://turbo.yandex.ru',
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
            'turboContent',
            'keywords',
            'guid',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function schemaPath(): string
    {
        return \dirname(__DIR__, 2) . '/schemas/yandex-turbo.xsd';
    }

    /**
     * {@inheritDoc}
     *
     * - `turboContent` (preferred) or `content` → `turbo:content` (CDATA)
     * - `content` → `content:encoded` (CDATA) when present
     * - `description` is not promoted to `content:encoded` automatically.
     *
     * @return array<string, mixed>
     */
    public function transform(FeedItem $item): array
    {
        $data = $this->baseTransform($item);
        $out  = $this->filterKeys($data, \array_merge($this->requiredFields(), $this->optionalFields()));

        // turboContent takes priority; fall back to content.
        $html = $out['turboContent'] ?? $out['content'] ?? null;
        if ($html !== null) {
            $out['turbo:content'] = (string) $html;
        }

        // content:encoded from the explicit content field only.
        if (isset($out['content'])) {
            $out['content:encoded'] = (string) $out['content'];
        }

        unset($out['turboContent'], $out['content']);

        return $out;
    }
}
