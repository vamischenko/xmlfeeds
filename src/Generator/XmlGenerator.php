<?php

declare(strict_types=1);

namespace XmlFeeds\Generator;

use XMLWriter;
use RuntimeException;
use XmlFeeds\Builder\FeedMeta;
use XmlFeeds\Contracts\FeedFormat;
use XmlFeeds\Contracts\PlatformInterface;
use XmlFeeds\Contracts\StreamableGeneratorInterface;

/**
 * Streams XML output via PHP's built-in {@see XMLWriter}.
 *
 * Dispatches to {@see emitRssFeed()} or {@see emitAtomFeed()} based on
 * {@see PlatformInterface::getFeedFormat()} — no coupling to concrete platform classes.
 *
 * Memory characteristics:
 *  - `generate()` builds the full document in a memory buffer and returns it as a string.
 *  - `generateToFile()` writes directly to a URI via `XMLWriter::openUri`, keeping RAM usage
 *    constant regardless of feed size.
 */
final class XmlGenerator implements StreamableGeneratorInterface
{
    /**
     * Field keys whose values are always wrapped in a CDATA section.
     *
     * Any value that contains `<` and `>` is also wrapped automatically
     * (see {@see CdataWrapper::prefersCdata()}).
     *
     * @var list<string>
     */
    private const CDATA_KEYS = [
        'content',
        'turboContent',
        'description',
        'content:encoded',
        'yandex:full-text',
        'turbo:content',
    ];

    /**
     * Generate the feed and return it as an XML string.
     *
     * @param PlatformInterface          $platform Target platform (format + namespaces).
     * @param FeedMeta                   $meta     Channel metadata.
     * @param list<array<string, mixed>> $items    Already-transformed item payloads.
     *
     * @return string Full XML document stored in a memory buffer.
     */
    public function generate(PlatformInterface $platform, FeedMeta $meta, array $items): string
    {
        $w = new XMLWriter();
        $w->openMemory();
        $this->emitDocument($w, $platform, $meta, $items);

        return (string) $w->outputMemory();
    }

    /**
     * Write the feed directly to a file path using `XMLWriter::openUri`.
     *
     * The full document is not retained as a PHP string; suitable for large feeds.
     *
     * @param string                     $path     Writable filesystem path.
     * @param PlatformInterface          $platform Target platform (format + namespaces).
     * @param FeedMeta                   $meta     Channel metadata.
     * @param list<array<string, mixed>> $items    Already-transformed item payloads.
     *
     * @return void
     *
     * @throws RuntimeException When the file cannot be opened for writing.
     */
    public function generateToFile(string $path, PlatformInterface $platform, FeedMeta $meta, array $items): void
    {
        $w = new XMLWriter();
        if ($w->openUri($path) === false) {
            throw new RuntimeException(\sprintf('Cannot open "%s" for writing.', $path));
        }
        $this->emitDocument($w, $platform, $meta, $items);
    }

    /**
     * Write the XML declaration and dispatch to the format-specific emitter.
     *
     * @param XMLWriter                    $w        Writer bound to memory (`openMemory`) or a file URI (`openUri`).
     * @param PlatformInterface            $platform Determines RSS vs Atom and namespace declarations.
     * @param FeedMeta                     $meta     Channel title, link, description, language, last build date.
     * @param list<array<string, mixed>>   $items    Per-item payloads after {@see PlatformInterface::transform()}.
     *
     * @return void
     */
    private function emitDocument(XMLWriter $w, PlatformInterface $platform, FeedMeta $meta, array $items): void
    {
        $w->startDocument('1.0', 'UTF-8');
        $w->setIndent(true);

        match ($platform->getFeedFormat()) {
            FeedFormat::Atom => $this->emitAtomFeed($w, $platform, $meta, $items),
            FeedFormat::Rss  => $this->emitRssFeed($w, $platform, $meta, $items),
        };

        $w->endDocument();
    }

    /**
     * Emit a complete RSS 2.0 document (`<rss version="2.0">` … `</rss>`).
     *
     * @param XMLWriter                    $w        Active writer positioned before any root output.
     * @param PlatformInterface            $platform Supplies `xmlns:*` declarations for the root element.
     * @param FeedMeta                     $meta     Channel-level elements inside `<channel>`.
     * @param list<array<string, mixed>>   $items    One `<item>` per entry.
     *
     * @return void
     */
    private function emitRssFeed(XMLWriter $w, PlatformInterface $platform, FeedMeta $meta, array $items): void
    {
        $w->startElement('rss');
        $w->writeAttribute('version', '2.0');

        foreach ($platform->namespace() as $prefix => $uri) {
            $w->writeAttribute("xmlns:{$prefix}", $uri);
        }

        $w->startElement('channel');

        $this->writeTextEl($w, 'title', $meta->title);
        $this->writeTextEl($w, 'link', $meta->link);
        $this->writeTextEl($w, 'description', $meta->description);
        $this->writeTextEl($w, 'language', $meta->language);

        if ($meta->lastBuildDate !== null) {
            $this->writeTextEl($w, 'lastBuildDate', XmlDate::toRfc2822($meta->lastBuildDate));
        }

        foreach ($items as $item) {
            $w->startElement('item');
            $this->writeRssItemBody($w, $platform->namespace(), $item);
            $w->endElement();
        }

        $w->endElement(); // channel
        $w->endElement(); // rss
    }

    /**
     * Write the body of a single RSS `<item>` element.
     *
     * Fields are emitted in a canonical order (title → link → guid → pubDate → description)
     * with all remaining keys appended alphabetically.
     *
     * @param array<string, string> $namespaces Prefix → URI map from the platform.
     * @param array<string, mixed>  $item       Transformed item payload.
     */
    private function writeRssItemBody(XMLWriter $w, array $namespaces, array $item): void
    {
        $ordered = $this->orderRssKeys(\array_keys($item));

        foreach ($ordered as $key) {
            if (!\array_key_exists($key, $item)) {
                continue;
            }
            $value = $item[$key];

            if ($key === 'enclosure' && \is_array($value)) {
                $this->writeEnclosure($w, $value);
                continue;
            }

            if (\is_array($value) && $key === 'category' && \array_is_list($value)) {
                foreach ($value as $c) {
                    $this->writeTextEl($w, 'category', (string) $c);
                }
                continue;
            }

            if (\is_array($value) && $key === 'keywords' && \array_is_list($value)) {
                $this->writePrefixedString(
                    $w,
                    $namespaces,
                    'keywords',
                    \implode(', ', \array_map(static fn (mixed $x): string => (string) $x, $value)),
                );
                continue;
            }

            if (\is_array($value) && $this->isMediaAttributeElement($key, $value)) {
                $this->writeMediaAttributeElement($w, $namespaces, $key, $value);
                continue;
            }

            if (\is_array($value) && $this->isNestedStructure($value)) {
                $this->writeNestedElement($w, $namespaces, $key, $value);
                continue;
            }

            if (!\is_string($value) && !\is_numeric($value)) {
                continue;
            }

            $this->writePrefixedString($w, $namespaces, $key, (string) $value);
        }
    }

    /**
     * Return `true` when the array is a non-empty associative structure
     * (i.e. not an indexed list), indicating it should be emitted as a
     * nested XML element rather than a scalar text node.
     *
     * @param array<mixed> $value
     */
    private function isNestedStructure(array $value): bool
    {
        return $value !== [] && !\array_is_list($value);
    }

    /**
     * Return `true` when the key starts with `media:` and the value contains a `url` attribute,
     * meaning it should be emitted as an element with XML attributes (e.g. `<media:content url="…"/>`).
     *
     * @param array<string, mixed> $value
     */
    private function isMediaAttributeElement(string $key, array $value): bool
    {
        return \str_starts_with($key, 'media:') && isset($value['url']);
    }

    /**
     * Emit a `media:*` element whose fields become XML attributes.
     *
     * @param array<string, string> $namespaces
     * @param array<string, mixed>  $attrs
     */
    private function writeMediaAttributeElement(XMLWriter $w, array $namespaces, string $name, array $attrs): void
    {
        [$prefix, $local] = $this->splitQName($name);
        if ($prefix !== null && isset($namespaces[$prefix])) {
            $w->startElementNS($prefix, $local, $namespaces[$prefix]);
        } else {
            $w->startElement($name);
        }
        foreach (['url', 'type', 'medium', 'width', 'height', 'length'] as $a) {
            if (isset($attrs[$a])) {
                $w->writeAttribute($a, (string) $attrs[$a]);
            }
        }
        if (isset($attrs['_text']) && $attrs['_text'] !== '') {
            $w->text((string) $attrs['_text']);
        }
        $w->endElement();
    }

    /**
     * Recursively emit a nested associative array as XML child elements.
     *
     * @param array<string, string> $namespaces
     * @param array<string, mixed>  $payload
     */
    private function writeNestedElement(XMLWriter $w, array $namespaces, string $name, array $payload): void
    {
        [$prefix, $local] = $this->splitQName($name);
        if ($prefix !== null && isset($namespaces[$prefix])) {
            $w->startElementNS($prefix, $local, $namespaces[$prefix]);
        } else {
            $w->startElement($name);
        }

        foreach ($payload as $childKey => $childVal) {
            if (\is_array($childVal) && $this->isNestedStructure($childVal)) {
                $this->writeNestedElement($w, $namespaces, $childKey, $childVal);
            } elseif (\is_array($childVal)) {
                foreach ($childVal as $one) {
                    $this->writePrefixedString($w, $namespaces, $childKey, (string) $one);
                }
            } else {
                $this->writePrefixedString($w, $namespaces, $childKey, (string) $childVal);
            }
        }

        $w->endElement();
    }

    /**
     * Emit an RSS `<enclosure>` element with `url`, `type`, and `length` attributes.
     *
     * @param array<string, mixed> $attrs
     */
    private function writeEnclosure(XMLWriter $w, array $attrs): void
    {
        $w->startElement('enclosure');
        if (isset($attrs['url'])) {
            $w->writeAttribute('url', (string) $attrs['url']);
        }
        if (isset($attrs['type'])) {
            $w->writeAttribute('type', (string) $attrs['type']);
        }
        if (isset($attrs['length'])) {
            $w->writeAttribute('length', (string) $attrs['length']);
        }
        $w->endElement();
    }

    /**
     * Write a single text or CDATA element, automatically resolving any namespace prefix.
     *
     * `pubDate` values are normalised to RFC 2822 before output.
     *
     * @param array<string, string> $namespaces
     */
    private function writePrefixedString(XMLWriter $w, array $namespaces, string $name, string $value): void
    {
        if ($name === 'pubDate') {
            $this->writeTextEl($w, 'pubDate', XmlDate::toRfc2822($value));
            return;
        }

        if ($this->useCdata($name, $value)) {
            $this->writeCdataEl($w, $namespaces, $name, $value);
            return;
        }

        $this->writeTextElPrefixed($w, $namespaces, $name, $value);
    }

    /**
     * Return `true` when the value should be wrapped in a CDATA section.
     *
     * Always true for keys in {@see CDATA_KEYS}; also true for any value
     * that looks like markup (contains both `<` and `>`).
     */
    private function useCdata(string $key, string $value): bool
    {
        return \in_array($key, self::CDATA_KEYS, true) || CdataWrapper::prefersCdata($value);
    }

    /**
     * Emit an element whose text content is a CDATA section.
     *
     * @param array<string, string> $namespaces
     */
    private function writeCdataEl(XMLWriter $w, array $namespaces, string $name, string $value): void
    {
        [$prefix, $local] = $this->splitQName($name);
        if ($prefix !== null && isset($namespaces[$prefix])) {
            $w->startElementNS($prefix, $local, $namespaces[$prefix]);
        } else {
            $w->startElement($name);
        }
        $w->writeCdata($value);
        $w->endElement();
    }

    /**
     * Emit a plain text element, resolving any namespace prefix.
     *
     * @param array<string, string> $namespaces
     */
    private function writeTextElPrefixed(XMLWriter $w, array $namespaces, string $name, string $value): void
    {
        [$prefix, $local] = $this->splitQName($name);
        if ($prefix !== null && isset($namespaces[$prefix])) {
            $w->startElementNS($prefix, $local, $namespaces[$prefix]);
        } else {
            $w->startElement($name);
        }
        $w->text($value);
        $w->endElement();
    }

    /**
     * Emit a plain text element in no namespace.
     */
    private function writeTextEl(XMLWriter $w, string $name, string $value): void
    {
        $w->startElement($name);
        $w->text($value);
        $w->endElement();
    }

    /**
     * Split a qualified name (`prefix:local`) into its parts.
     *
     * Returns `[null, $name]` when there is no colon.
     *
     * @return array{0: string|null, 1: string}
     */
    private function splitQName(string $name): array
    {
        if (!\str_contains($name, ':')) {
            return [null, $name];
        }
        $parts = \explode(':', $name, 2);

        return [$parts[0], $parts[1]];
    }

    /**
     * Sort item field keys into a canonical RSS order.
     *
     * Priority fields (title → link → guid → pubDate → description) come first;
     * remaining keys are sorted alphabetically.
     *
     * @param list<string> $keys
     *
     * @return list<string>
     */
    private function orderRssKeys(array $keys): array
    {
        $priority = [
            'title'       => 0,
            'link'        => 1,
            'guid'        => 2,
            'pubDate'     => 3,
            'description' => 4,
        ];

        \usort($keys, static function (string $a, string $b) use ($priority): int {
            $pa = $priority[$a] ?? 100;
            $pb = $priority[$b] ?? 100;

            return $pa !== $pb ? $pa <=> $pb : \strcmp($a, $b);
        });

        return $keys;
    }

    /**
     * Emit a complete Atom 1.0 document (`<feed xmlns="http://www.w3.org/2005/Atom">` …).
     *
     * @param XMLWriter                    $w        Active writer.
     * @param PlatformInterface            $platform Atom platforms may declare extra `xmlns:*` on `<feed>`.
     * @param FeedMeta                     $meta     Channel title, links, subtitle, id, updated.
     * @param list<array<string, mixed>>   $items    One `<entry>` per item.
     *
     * @return void
     */
    private function emitAtomFeed(XMLWriter $w, PlatformInterface $platform, FeedMeta $meta, array $items): void
    {
        $atomNs = 'http://www.w3.org/2005/Atom';
        $w->startElementNS(null, 'feed', $atomNs);

        foreach ($platform->namespace() as $prefix => $uri) {
            if ($prefix !== '') {
                $w->writeAttribute("xmlns:{$prefix}", $uri);
            }
        }

        $w->startElementNS(null, 'title', $atomNs);
        $w->writeAttribute('type', 'text');
        $w->text($meta->title);
        $w->endElement();

        $w->startElementNS(null, 'link', $atomNs);
        $w->writeAttribute('href', $meta->link);
        $w->writeAttribute('rel', 'alternate');
        $w->endElement();

        $w->startElementNS(null, 'subtitle', $atomNs);
        $w->text($meta->description);
        $w->endElement();

        $w->startElementNS(null, 'id', $atomNs);
        $w->text($meta->link);
        $w->endElement();

        $updated = $meta->lastBuildDate ?? new \DateTimeImmutable('now');
        $this->writeAtomTextEl($w, $atomNs, 'updated', XmlDate::toAtom($updated));

        foreach ($items as $item) {
            $w->startElementNS(null, 'entry', $atomNs);
            $this->writeAtomEntry($w, $atomNs, $item);
            $w->endElement();
        }

        $w->endElement(); // feed
    }

    /**
     * Emit a single Atom `<entry>` element.
     *
     * @param array<string, mixed> $item
     */
    private function writeAtomEntry(XMLWriter $w, string $atomNs, array $item): void
    {
        $title = (string) ($item['title'] ?? '');
        $w->startElementNS(null, 'title', $atomNs);
        $w->writeAttribute('type', 'html');
        $w->text($title);
        $w->endElement();

        $link = (string) ($item['link'] ?? '');
        $w->startElementNS(null, 'link', $atomNs);
        $w->writeAttribute('href', $link);
        $w->writeAttribute('rel', 'alternate');
        $w->endElement();

        $id = (string) ($item['guid'] ?? $link);
        $w->startElementNS(null, 'id', $atomNs);
        $w->text($id);
        $w->endElement();

        $pub = $item['pubDate'] ?? 'now';
        $this->writeAtomTextEl($w, $atomNs, 'updated', XmlDate::toAtom($pub));
        $this->writeAtomTextEl($w, $atomNs, 'published', XmlDate::toAtom($pub));

        if (isset($item['author'])) {
            $w->startElementNS(null, 'author', $atomNs);
            $this->writeAtomTextEl($w, $atomNs, 'name', (string) $item['author']);
            $w->endElement();
        }

        if (isset($item['content'])) {
            $w->startElementNS(null, 'content', $atomNs);
            $w->writeAttribute('type', 'html');
            $w->writeCdata((string) $item['content']);
            $w->endElement();
        } elseif (isset($item['description'])) {
            $w->startElementNS(null, 'summary', $atomNs);
            $w->writeAttribute('type', 'html');
            $w->writeCdata((string) $item['description']);
            $w->endElement();
        }

        if (isset($item['category'])) {
            $cats = \is_array($item['category']) ? $item['category'] : [$item['category']];
            foreach ($cats as $cat) {
                $w->startElementNS(null, 'category', $atomNs);
                $w->writeAttribute('term', (string) $cat);
                $w->endElement();
            }
        }
    }

    /**
     * Emit a simple Atom text element (no attributes).
     */
    private function writeAtomTextEl(XMLWriter $w, string $atomNs, string $name, string $value): void
    {
        $w->startElementNS(null, $name, $atomNs);
        $w->text($value);
        $w->endElement();
    }
}
