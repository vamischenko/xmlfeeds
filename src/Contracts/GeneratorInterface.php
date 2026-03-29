<?php

declare(strict_types=1);

namespace XmlFeeds\Contracts;

use XmlFeeds\Builder\FeedMeta;

/**
 * Converts a platform, channel metadata, and a list of transformed items into
 * an XML string.
 *
 * The built-in implementation is {@see \XmlFeeds\Generator\XmlGenerator}.
 * Implement this interface to plug in a custom serialisation strategy
 * (e.g. a template-based or JSON-based generator).
 *
 * For generators that can write directly to a file without buffering the full
 * XML in memory, additionally implement {@see StreamableGeneratorInterface}.
 */
interface GeneratorInterface
{
    /**
     * Generate the feed and return it as a UTF-8 XML string.
     *
     * @param PlatformInterface          $platform Target platform (provides namespaces and format).
     * @param FeedMeta                   $meta     Channel-level metadata (title, link, description …).
     * @param list<array<string, mixed>> $items    Already-transformed item payloads.
     *
     * @return string Complete XML document, including the `<?xml …?>` declaration.
     */
    public function generate(PlatformInterface $platform, FeedMeta $meta, array $items): string;
}
