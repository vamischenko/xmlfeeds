<?php

declare(strict_types=1);

namespace XmlFeeds\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use XmlFeeds\Builder\FeedItem;
use XmlFeeds\Builder\FeedMeta;
use XmlFeeds\Generator\XmlGenerator;
use XmlFeeds\Platforms\AtomFeed;
use XmlFeeds\Platforms\GoogleNews;
use XmlFeeds\Platforms\MailRuNews;
use XmlFeeds\Platforms\RamblerNews;
use XmlFeeds\Platforms\RssFeed;
use XmlFeeds\Platforms\YandexNews;
use XmlFeeds\Platforms\YandexTurbo;
use XmlFeeds\Platforms\YandexZen;

final class XmlGeneratorPlatformsTest extends TestCase
{
    private function meta(): FeedMeta
    {
        return new FeedMeta('T', 'https://example.com', 'D', 'ru', new DateTimeImmutable());
    }

    /**
     * @return array<string, mixed>
     */
    private function baseItem(): array
    {
        return [
            'title' => 'Post',
            'link' => 'https://example.com/p/1',
            'pubDate' => new DateTimeImmutable('2026-03-01 10:00:00 UTC'),
            'description' => 'Lead',
        ];
    }

    public function testAllPlatformsProduceXml(): void
    {
        $gen = new XmlGenerator();
        $meta = $this->meta();
        $item = FeedItem::fromArray($this->baseItem() + ['content' => '<p>Full</p>', 'turboContent' => '<div>Turbo</div>']);

        $platforms = [
            new RssFeed(),
            new YandexNews(),
            new YandexZen(),
            new GoogleNews(),
            new RamblerNews(),
            new MailRuNews(),
            new YandexTurbo(),
            new AtomFeed(),
        ];

        foreach ($platforms as $p) {
            $t = $p->transform($item);
            $xml = $gen->generate($p, $meta, [$t]);
            self::assertNotSame('', $xml);
            self::assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        }
    }

    public function testAtomFeedUsesAtomNamespace(): void
    {
        $gen = new XmlGenerator();
        $p = new AtomFeed();
        $item = FeedItem::fromArray($this->baseItem());
        $xml = $gen->generate($p, $this->meta(), [$p->transform($item)]);
        self::assertStringContainsString('http://www.w3.org/2005/Atom', $xml);
        self::assertStringContainsString('<feed', $xml);
    }

    public function testGoogleNewsHasNewsBlock(): void
    {
        $gen = new XmlGenerator();
        $p = new GoogleNews();
        $item = FeedItem::fromArray($this->baseItem() + ['keywords' => ['a', 'b']]);
        $xml = $gen->generate($p, $this->meta(), [$p->transform($item)]);
        self::assertStringContainsString('news:news', $xml);
        self::assertStringContainsString('news:publication', $xml);
    }
}
