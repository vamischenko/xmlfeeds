<?php

declare(strict_types=1);

namespace XmlFeeds\Platforms;

use XmlFeeds\Builder\FeedItem;

/**
 * Google News feed platform (RSS 2.0 + `news:*` / `content:*`).
 *
 * Generates an RSS 2.0 feed that conforms to the Google News Sitemap extension.
 * Each item is wrapped in a `<news:news>` element containing the publication
 * metadata required by Google News.
 *
 * Namespace extensions:
 *  - `news`    — `http://www.google.com/schemas/sitemap-news/0.9`
 *  - `content` — `http://purl.org/rss/1.0/modules/content/`
 *
 * Required fields: `title`, `link`, `pubDate`.
 *
 * Optional fields: `description`, `author`, `category`, `enclosure`,
 * `content`, `keywords`, `guid`, `publication_name`, `publication_language`.
 *
 * @see https://developers.google.com/search/docs/crawling-indexing/sitemaps/news-sitemap
 */
final class GoogleNews extends AbstractRssPlatform
{
    /**
     * {@inheritDoc}
     */
    public function namespace(): array
    {
        return [
            'news'    => 'http://www.google.com/schemas/sitemap-news/0.9',
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
            'publication_name',
            'publication_language',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function schemaPath(): string
    {
        return \dirname(__DIR__, 2) . '/schemas/google-news.xsd';
    }

    /**
     * {@inheritDoc}
     *
     * Builds the nested `<news:news>` block. `pubDate` must be non-empty and parseable.
     *
     * @return array<string, mixed>
     *
     * @throws \InvalidArgumentException When `pubDate` is missing or empty.
     */
    public function transform(FeedItem $item): array
    {
        $data = $this->baseTransform($item);
        $flat = $this->filterKeys($data, \array_merge($this->requiredFields(), $this->optionalFields()));

        $pub = $flat['pubDate'] ?? null;
        if ($pub === null || $pub === '') {
            throw new \InvalidArgumentException(
                'GoogleNews platform requires a non-empty "pubDate" field.',
            );
        }

        if ($pub instanceof \DateTimeInterface) {
            $pubStr = $pub->format('Y-m-d');
        } else {
            $pubStr = (new \DateTimeImmutable((string) $pub))->format('Y-m-d');
        }

        $name = (string) ($flat['publication_name'] ?? 'Publication');
        $lang = (string) ($flat['publication_language'] ?? 'ru');

        unset($flat['publication_name'], $flat['publication_language']);

        $flat['news:news'] = [
            'news:publication' => [
                'news:name'     => $name,
                'news:language' => $lang,
            ],
            'news:publication_date' => $pubStr,
            'news:title'            => (string) ($flat['title'] ?? ''),
        ];

        if (isset($flat['keywords'])) {
            $kw = $flat['keywords'];
            $flat['news:news']['news:keywords'] = \is_array($kw)
                ? \implode(', ', $kw)
                : (string) $kw;
        }

        if (isset($flat['content'])) {
            $flat['content:encoded'] = (string) $flat['content'];
            unset($flat['content']);
        }

        return $flat;
    }
}
