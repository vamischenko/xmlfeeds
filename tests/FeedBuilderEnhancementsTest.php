<?php

declare(strict_types=1);

namespace XmlFeeds\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use XmlFeeds\Builder\FeedMeta;
use XmlFeeds\Contracts\GeneratorInterface;
use XmlFeeds\Contracts\PlatformInterface;
use XmlFeeds\Builder\FeedBuilder;
use XmlFeeds\Exceptions\UnsupportedFieldException;
use XmlFeeds\Platforms\RssFeed;

final class FeedBuilderEnhancementsTest extends TestCase
{
    public function testStrictFieldsRejectsUnknownKey(): void
    {
        $this->expectException(UnsupportedFieldException::class);

        FeedBuilder::for(new RssFeed())
            ->title('S')
            ->link('https://example.com')
            ->description('D')
            ->strictFields(true)
            ->items([['title' => 't', 'link' => 'https://example.com/a', 'unknown_field' => 'x']], static fn (array $a): array => $a)
            ->withoutValidation()
            ->build();
    }

    public function testBuildToFileWritesXml(): void
    {
        $path = sys_get_temp_dir().'/xmlfeeds-test-'.uniqid('', true).'.xml';
        try {
            FeedBuilder::for(new RssFeed())
                ->title('S')
                ->link('https://example.com')
                ->description('D')
                ->items([['title' => 't', 'link' => 'https://example.com/a']], static fn (array $a): array => $a)
                ->withoutValidation()
                ->buildToFile($path);

            self::assertFileExists($path);
            $content = (string) file_get_contents($path);
            self::assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $content);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testCustomGeneratorIsUsed(): void
    {
        $gen = new class implements GeneratorInterface {
            public function generate(PlatformInterface $platform, FeedMeta $meta, array $items): string
            {
                return '<xml/>';
            }
        };

        $xml = FeedBuilder::for(new RssFeed())
            ->generator($gen)
            ->title('S')
            ->link('https://example.com')
            ->description('D')
            ->items([], static fn (): array => [])
            ->withoutValidation()
            ->build();

        self::assertSame('<xml/>', $xml);
    }

    public function testLoggerReceivesMessageOnValidationFailure(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('log')
            ->with(
                self::identicalTo(LogLevel::ERROR),
                self::stringContains('validation failed'),
                self::arrayHasKey('errors'),
            );

        try {
            FeedBuilder::for(new RssFeed())
                ->logger($logger)
                ->title('S')
                ->link('https://example.com')
                ->description('D')
                ->items([['title' => 'x', 'link' => 'bad', 'pubDate' => new DateTimeImmutable()]], static fn (array $a): array => $a)
                ->withValidation(true)
                ->build();
        } catch (\XmlFeeds\Exceptions\ValidationException) {
        }
    }
}
