<?php

declare(strict_types=1);

namespace XmlFeeds\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use XmlFeeds\Builder\FeedMeta;
use XmlFeeds\Generator\XmlGenerator;
use XmlFeeds\Platforms\RssFeed;

final class XmlGeneratorStreamTest extends TestCase
{
    public function testGenerateToFileMatchesGenerate(): void
    {
        $path = sys_get_temp_dir().'/xmlfeeds-gen-'.uniqid('', true).'.xml';
        $gen = new XmlGenerator();
        $p = new RssFeed();
        $meta = new FeedMeta('T', 'https://example.com', 'D', 'ru', new DateTimeImmutable());
        $items = [
            [
                'title' => 'A',
                'link' => 'https://example.com/1',
                'pubDate' => new DateTimeImmutable('@1'),
            ],
        ];

        try {
            $gen->generateToFile($path, $p, $meta, $items);
            $fromFile = (string) file_get_contents($path);
            $fromString = $gen->generate($p, $meta, $items);
            self::assertSame($fromString, $fromFile);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
