<?php

declare(strict_types=1);

namespace XmlFeeds\Platforms;

use XmlFeeds\Builder\FeedItem;
use XmlFeeds\Contracts\FeedFormat;
use XmlFeeds\Contracts\PlatformInterface;

/**
 * Base class for all RSS 2.0 platforms.
 *
 * Provides:
 *  - {@see getFeedFormat()} → `FeedFormat::Rss` (shared by all subclasses)
 *  - {@see baseTransform()} — returns raw item data as an array
 *  - {@see filterKeys()} — whitelists field keys to those declared by the platform
 *
 * Subclasses only need to implement the platform-specific parts:
 * `namespace()`, `requiredFields()`, `optionalFields()`, `schemaPath()`,
 * and `transform()`.
 */
abstract class AbstractRssPlatform implements PlatformInterface
{
    /**
     * All subclasses produce RSS 2.0 output.
     *
     * @return FeedFormat Always {@see FeedFormat::Rss}.
     */
    public function getFeedFormat(): FeedFormat
    {
        return FeedFormat::Rss;
    }

    /**
     * Return the item's raw data array without any transformation.
     *
     * @param FeedItem $item User-supplied item wrapping the mapper output.
     *
     * @return array<string, mixed> Same keys and values as {@see FeedItem::toArray()}.
     */
    protected function baseTransform(FeedItem $item): array
    {
        return $item->toArray();
    }

    /**
     * Return only the keys present in `$allowed`, preserving their values.
     *
     * Used by `transform()` implementations to strip unrecognised fields
     * before passing data to the generator.
     *
     * @param array<string, mixed> $data    Full mapper output.
     * @param list<string>         $allowed Whitelist of keys to keep.
     *
     * @return array<string, mixed> Subset of `$data` containing only allowed keys.
     */
    protected function filterKeys(array $data, array $allowed): array
    {
        $out = [];
        foreach ($allowed as $key) {
            if (\array_key_exists($key, $data)) {
                $out[$key] = $data[$key];
            }
        }

        return $out;
    }
}
