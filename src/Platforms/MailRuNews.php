<?php

declare(strict_types=1);

namespace XmlFeeds\Platforms;

/**
 * Mail.ru News feed platform (RSS 2.0 + `content:encoded`).
 *
 * Generates a standard RSS 2.0 feed compatible with the Mail.ru news
 * aggregator. Full article text is delivered via `content:encoded` (CDATA).
 *
 * Namespace extensions:
 *  - `content` — `http://purl.org/rss/1.0/modules/content/`
 *
 * Required fields: `title`, `link`, `pubDate`.
 *
 * Optional fields: `description`, `author`, `category`, `enclosure`,
 * `content`, `keywords`, `guid`.
 */
final class MailRuNews extends AbstractContentRssPlatform
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
