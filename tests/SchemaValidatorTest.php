<?php

declare(strict_types=1);

namespace XmlFeeds\Tests;

use PHPUnit\Framework\TestCase;
use XmlFeeds\Platforms\RssFeed;
use XmlFeeds\Validator\SchemaValidator;

final class SchemaValidatorTest extends TestCase
{
    public function testXsdValidationPassesForSampleRss(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"><channel><title>a</title><link>https://x</link><description>b</description></channel></rss>
XML;
        $path = dirname(__DIR__).'/schemas/rss-2.0.xsd';
        $v = new SchemaValidator();
        $r = $v->validate($xml, $path);
        self::assertTrue($r->isValid(), implode('; ', $r->errors()));
    }

    public function testFullValidationWithPlatform(): void
    {
        $platform = new RssFeed();
        $items = [
            [
                'title' => 't',
                'link' => 'https://example.com/a',
                'pubDate' => '2026-01-01',
            ],
        ];
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"><channel><title>x</title><link>https://example.com</link><description>d</description><item><title>t</title><link>https://example.com/a</link><pubDate>Wed, 01 Jan 2026 00:00:00 +0000</pubDate></item></channel></rss>
XML;
        $v = new SchemaValidator();
        $r = $v->validateFull($platform, $items, $xml);
        self::assertTrue($r->isValid());
    }
}
