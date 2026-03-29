<?php

declare(strict_types=1);

namespace XmlFeeds\Builder;

use XmlFeeds\Contracts\FeedItemInterface;

/**
 * Immutable DTO that holds the raw field data for a single feed item.
 *
 * Instances are created by {@see FeedBuilder::items()} from the array
 * returned by the user-supplied mapper callable, and are later passed to
 * {@see \XmlFeeds\Contracts\PlatformInterface::transform()} for platform-specific
 * field mapping before XML generation.
 */
final readonly class FeedItem implements FeedItemInterface
{
    /**
     * @param array<string, mixed> $data Raw field map produced by the mapper callable; stored immutably.
     */
    public function __construct(
        private readonly array $data,
    ) {
    }

    /**
     * Create a new instance from a field array.
     *
     * @param array<string, mixed> $data Raw key/value pairs from the user mapper.
     *
     * @return self Immutable item wrapping `$data`.
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Return the raw field map.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
