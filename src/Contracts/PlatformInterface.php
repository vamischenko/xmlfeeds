<?php

declare(strict_types=1);

namespace XmlFeeds\Contracts;

use XmlFeeds\Builder\FeedItem;

/**
 * Describes a target platform for XML feed generation.
 *
 * A platform encapsulates three concerns:
 *  - **Format** — RSS 2.0 or Atom 1.0 (via {@see getFeedFormat()}).
 *  - **Schema** — which fields are required/optional and an optional XSD path.
 *  - **Transformation** — how a generic {@see FeedItem} is mapped to the
 *    platform's specific field names and structure.
 *
 * To add a new platform, implement this interface (or extend
 * {@see \XmlFeeds\Platforms\AbstractRssPlatform} for RSS 2.0 variants).
 */
interface PlatformInterface
{
    /**
     * The serialisation format this platform produces.
     *
     * {@see XmlGenerator} uses this to select the correct rendering path
     * without inspecting the concrete class.
     *
     * @return FeedFormat Either {@see FeedFormat::Rss} or {@see FeedFormat::Atom}.
     */
    public function getFeedFormat(): FeedFormat;

    /**
     * XML namespace declarations added to the root element.
     *
     * Keys are namespace prefixes (e.g. `'yandex'`), values are URIs
     * (e.g. `'http://news.yandex.ru'`).
     *
     * @return array<string, string> Prefix => namespace URI for `xmlns:prefix` attributes.
     */
    public function namespace(): array;

    /**
     * Fields that every item *must* contain for the feed to be valid.
     *
     * @return list<string> Mapper/transform keys that must be present on each item.
     */
    public function requiredFields(): array;

    /**
     * Fields that items *may* contain in addition to the required ones.
     *
     * @return list<string> Additional mapper keys recognised by this platform.
     */
    public function optionalFields(): array;

    /**
     * Absolute path to the XSD schema used for document-level validation,
     * or `null` if no schema is available for this platform.
     *
     * @return string|null Filesystem path to a readable `.xsd` file, or `null` to skip XSD checks.
     */
    public function schemaPath(): ?string;

    /**
     * Map a generic feed item to the field array expected by this platform.
     *
     * Implementations should rename, drop, or reformat fields as needed.
     * The returned array is passed directly to the generator.
     *
     * @return array<string, mixed> Keys may include prefixed names (e.g. `yandex:full-text`) understood by {@see \XmlFeeds\Generator\XmlGenerator}.
     */
    public function transform(FeedItem $item): array;
}
