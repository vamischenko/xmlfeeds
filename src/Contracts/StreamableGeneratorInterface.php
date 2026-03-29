<?php

declare(strict_types=1);

namespace XmlFeeds\Contracts;

use XmlFeeds\Builder\FeedMeta;

/**
 * A generator that can write XML directly to a file without holding the full
 * document in memory.
 *
 * Extends {@see GeneratorInterface} with a `generateToFile()` method.
 * {@see FeedBuilder::buildToFile()} checks for this interface and delegates
 * to `generateToFile()` when available, keeping RAM usage constant for large
 * feeds.
 *
 * The built-in implementation is {@see \XmlFeeds\Generator\XmlGenerator},
 * which writes via `XMLWriter::openUri`.
 */
interface StreamableGeneratorInterface extends GeneratorInterface
{
    /**
     * Generate the feed and write it to `$path`.
     *
     * @param string                     $path     Destination file path (overwritten if it exists).
     * @param PlatformInterface          $platform Target platform.
     * @param FeedMeta                   $meta     Channel-level metadata.
     * @param list<array<string, mixed>> $items    Already-transformed item payloads.
     *
     * @return void
     *
     * @throws \RuntimeException When the file cannot be opened for writing.
     */
    public function generateToFile(string $path, PlatformInterface $platform, FeedMeta $meta, array $items): void;
}
