<?php

declare(strict_types=1);

namespace XmlFeeds\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use XmlFeeds\Builder\FeedBuilder;
use XmlFeeds\Platforms\RssFeed;
use XmlFeeds\Platforms\YandexNews;

final class FeedBuilderTest extends TestCase
{
    public function testRssFeedBuildsValidXml(): void
    {
        $articles = [
            [
                'title' => 'Hello',
                'link' => 'https://example.com/a',
                'pubDate' => new DateTimeImmutable('2026-01-15 12:00:00 UTC'),
                'description' => 'Short',
            ],
        ];

        $xml = FeedBuilder::for(new RssFeed())
            ->title('Site')
            ->link('https://example.com')
            ->description('News')
            ->items($articles, static fn (array $a): array => $a)
            ->withoutValidation()
            ->build();

        self::assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        self::assertStringContainsString('<title>Hello</title>', $xml);
        self::assertStringContainsString('https://example.com/a', $xml);
    }

    public function testYandexNewsIncludesNamespaceAndFullText(): void
    {
        $items = [
            [
                'title' => 'T',
                'link' => 'https://example.com/x',
                'pubDate' => new DateTimeImmutable('@1700000000'),
                'content' => '<p>Body</p>',
                'source' => 'Example',
                'genre' => 'article',
            ],
        ];

        $xml = FeedBuilder::for(new YandexNews())
            ->title('S')
            ->link('https://example.com')
            ->description('D')
            ->items($items, static fn (array $a): array => $a)
            ->withoutValidation()
            ->build();

        self::assertStringContainsString('xmlns:yandex', $xml);
        self::assertStringContainsString('yandex:full-text', $xml);
        self::assertStringContainsString('<p>Body</p>', $xml);
    }

    public function testValidationThrowsOnBadLink(): void
    {
        $this->expectException(\XmlFeeds\Exceptions\ValidationException::class);

        FeedBuilder::for(new RssFeed())
            ->title('S')
            ->link('https://example.com')
            ->description('D')
            ->items([['title' => 'x', 'link' => 'not-a-url', 'pubDate' => new DateTimeImmutable()]], static fn (array $a): array => $a)
            ->withValidation(true)
            ->build();
    }
}
