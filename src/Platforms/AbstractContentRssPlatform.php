<?php

declare(strict_types=1);

namespace XmlFeeds\Platforms;

use XmlFeeds\Builder\FeedItem;

/**
 * Base class for RSS 2.0 platforms that publish full text via `content:encoded`.
 *
 * Behaviour:
 *  - `content` field → mapped to `content:encoded` (CDATA), then removed.
 *  - If no `content`, `description` is also copied to `content:encoded`.
 *
 * Subclasses that use this base: {@see RamblerNews}, {@see MailRuNews},
 * {@see TelegramChannel}, {@see VkontakteFeed}, {@see FacebookFeed}.
 *
 * Subclasses must still implement `namespace()`, `requiredFields()`,
 * `optionalFields()`, and `schemaPath()`.
 */
abstract class AbstractContentRssPlatform extends AbstractRssPlatform
{
    /**
     * Map item fields and promote `content` (or `description`) to `content:encoded`.
     *
     * @param FeedItem $item Raw item from the builder; typically contains `content` or `description`.
     *
     * @return array<string, mixed> Payload for {@see \XmlFeeds\Generator\XmlGenerator} including `content:encoded` when applicable.
     */
    public function transform(FeedItem $item): array
    {
        $data = $this->baseTransform($item);
        $out  = $this->filterKeys($data, \array_merge($this->requiredFields(), $this->optionalFields()));

        if (isset($out['content'])) {
            $out['content:encoded'] = (string) $out['content'];
            unset($out['content']);
        } elseif (isset($out['description'])) {
            $out['content:encoded'] = (string) $out['description'];
        }

        return $out;
    }
}
