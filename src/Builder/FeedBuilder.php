<?php

declare(strict_types=1);

namespace XmlFeeds\Builder;

use DateTimeInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use XmlFeeds\Contracts\GeneratorInterface;
use XmlFeeds\Contracts\PlatformInterface;
use XmlFeeds\Contracts\SchemaValidatorInterface;
use XmlFeeds\Contracts\StreamableGeneratorInterface;
use XmlFeeds\Contracts\ValidatorInterface;
use XmlFeeds\Exceptions\UnsupportedFieldException;
use XmlFeeds\Exceptions\ValidationException;
use XmlFeeds\Generator\XmlGenerator;
use XmlFeeds\Validator\SchemaValidator;
use XmlFeeds\Validator\ValidationResult;

/**
 * Entry point for building XML feeds via a Fluent API.
 *
 * Typical usage:
 * ```php
 * $xml = FeedBuilder::for(new YandexNews())
 *     ->title('My site')
 *     ->link('https://example.com')
 *     ->description('News')
 *     ->items($articles, fn($a) => [
 *         'title'   => $a->title,
 *         'link'    => $a->url,
 *         'pubDate' => $a->published_at,
 *         'content' => $a->body,
 *     ])
 *     ->withValidation()
 *     ->build();
 * ```
 *
 * All setter methods return `$this` to allow chaining.
 */
final class FeedBuilder
{
    private ?string $title = null;

    private ?string $link = null;

    private ?string $description = null;

    private string $language = 'ru';

    private ?DateTimeInterface $lastBuildDate = null;

    /** @var list<FeedItem> */
    private array $feedItems = [];

    private bool $validationEnabled = true;

    private bool $validationThrow = true;

    private bool $strictFields = false;

    private ?ValidatorInterface $customValidator = null;

    /**
     * @param PlatformInterface           $platform         Target feed platform (RSS or Atom).
     * @param SchemaValidatorInterface    $schemaValidator Built-in or custom field/XML/XSD validator.
     * @param GeneratorInterface          $generator       Serialises {@see FeedMeta} and items to XML.
     * @param LoggerInterface             $logger          Receives validation failure messages (defaults to silent {@see NullLogger}).
     */
    public function __construct(
        private PlatformInterface $platform,
        private SchemaValidatorInterface $schemaValidator = new SchemaValidator(),
        private GeneratorInterface $generator = new XmlGenerator(),
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * Create a new builder for the given platform.
     *
     * @param PlatformInterface $platform Defines namespaces, field rules, and output format.
     *
     * @return self Fluent builder instance.
     */
    public static function for(PlatformInterface $platform): self
    {
        return new self($platform);
    }

    /**
     * Replace the XML generator (e.g. a custom streaming implementation).
     *
     * @param GeneratorInterface $generator New serialiser implementation.
     *
     * @return $this
     *
     * @see StreamableGeneratorInterface for file-based generation support.
     */
    public function generator(GeneratorInterface $generator): self
    {
        $this->generator = $generator;

        return $this;
    }

    /**
     * Replace the schema/structural validator.
     *
     * @param SchemaValidatorInterface $schemaValidator Custom implementation (often {@see SchemaValidator}).
     *
     * @return $this
     *
     * Accepts any implementation of {@see SchemaValidatorInterface},
     * making it easy to swap in a custom or mock validator.
     */
    public function schemaValidator(SchemaValidatorInterface $schemaValidator): self
    {
        $this->schemaValidator = $schemaValidator;

        return $this;
    }

    /**
     * Set a PSR-3 logger for validation failure messages.
     *
     * @param LoggerInterface|null $logger Pass `null` to revert to the silent {@see NullLogger}.
     *
     * @return $this
     */
    public function logger(?LoggerInterface $logger): self
    {
        $this->logger = $logger ?? new NullLogger();

        return $this;
    }

    /**
     * When enabled, any mapper key not listed in the platform's
     * `requiredFields()` or `optionalFields()` will throw {@see UnsupportedFieldException}.
     *
     * @param bool $enabled Whether to enforce the whitelist on the next {@see items()} call.
     *
     * @return $this
     */
    public function strictFields(bool $enabled = true): self
    {
        $this->strictFields = $enabled;

        return $this;
    }

    /**
     * Set the channel title (required before calling {@see build()}).
     *
     * @param string $title Visible channel name in the feed.
     *
     * @return $this
     */
    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the channel URL (required before calling {@see build()}).
     *
     * @param string $url Canonical site URL for the channel.
     *
     * @return $this
     */
    public function link(string $url): self
    {
        $this->link = $url;

        return $this;
    }

    /**
     * Set the channel description (required before calling {@see build()}).
     *
     * @param string $desc Short summary of the feed content.
     *
     * @return $this
     */
    public function description(string $desc): self
    {
        $this->description = $desc;

        return $this;
    }

    /**
     * Set the channel language code (default: `'ru'`).
     *
     * @param string $lang BCP 47 language tag (e.g. `ru`, `en`).
     *
     * @return $this
     */
    public function language(string $lang): self
    {
        $this->language = $lang;

        return $this;
    }

    /**
     * Set the channel `<lastBuildDate>` / Atom `<updated>` source value.
     *
     * @param DateTimeInterface $date Last time the feed content changed.
     *
     * @return $this
     */
    public function lastBuildDate(DateTimeInterface $date): self
    {
        $this->lastBuildDate = $date;

        return $this;
    }

    /**
     * Provide the list of items and a mapper callable that converts each row
     * into a field array understood by the selected platform.
     *
     * Replaces any previously set items on repeated calls.
     *
     * @param iterable<mixed>                        $items  Source collection (array, Generator, …).
     * @param callable(mixed): array<string, mixed>  $mapper Converts one source row to a field array.
     *
     * @return $this
     */
    public function items(iterable $items, callable $mapper): self
    {
        $this->feedItems = [];
        foreach ($items as $row) {
            /** @var array<string, mixed> $mapped */
            $mapped = $mapper($row);
            if ($this->strictFields) {
                $this->assertAllowedMapperKeys($mapped);
            }
            $this->feedItems[] = FeedItem::fromArray($mapped);
        }

        return $this;
    }

    /**
     * Enable validation (default).
     *
     * @param bool $throw When `true` (default), a {@see ValidationException} is thrown on failure.
     *                    When `false`, errors are logged and execution continues.
     *
     * @return $this
     */
    public function withValidation(bool $throw = true): self
    {
        $this->validationEnabled = true;
        $this->validationThrow   = $throw;

        return $this;
    }

    /**
     * Disable all validation; only XML generation runs.
     *
     * @return $this
     */
    public function withoutValidation(): self
    {
        $this->validationEnabled = false;

        return $this;
    }

    /**
     * Attach an additional custom XML validator that runs after the built-in one.
     *
     * @param ValidatorInterface|null $validator Pass `null` to remove a previously set validator.
     *
     * @return $this
     */
    public function validator(?ValidatorInterface $validator): self
    {
        $this->customValidator = $validator;

        return $this;
    }

    /**
     * Generate the feed and return it as an XML string.
     *
     * @return string UTF-8 XML document.
     *
     * @throws \LogicException       When `title`, `link`, or `description` have not been set.
     * @throws ValidationException   When validation is enabled, `$throw = true`, and the feed is invalid.
     */
    public function build(): string
    {
        $meta        = $this->requireMeta();
        $transformed = $this->transformedItems();

        $xml = $this->generator->generate($this->platform, $meta, $transformed);

        if ($this->validationEnabled) {
            $this->runValidation($transformed, $xml);
        }

        return $xml;
    }

    /**
     * Generate the feed and write it directly to a file.
     *
     * When the configured generator implements {@see StreamableGeneratorInterface},
     * the XML is written via `XMLWriter::openUri` (no full string kept in RAM).
     * Otherwise the XML is built in memory and written with `file_put_contents`.
     *
     * Validation (if enabled) runs against the already-generated XML string so
     * the file is not read back from disk.
     *
     * @param string $path Filesystem path to write (created or overwritten).
     *
     * @return void
     *
     * @throws \LogicException    When channel metadata has not been set.
     * @throws \RuntimeException  When the file cannot be written.
     * @throws ValidationException When validation is enabled, `$throw = true`, and the feed is invalid.
     */
    public function buildToFile(string $path): void
    {
        $meta        = $this->requireMeta();
        $transformed = $this->transformedItems();

        if ($this->generator instanceof StreamableGeneratorInterface) {
            // Build to file first, then validate from the string we already have in memory.
            // We re-generate in memory only for validation to avoid a file re-read.
            $this->generator->generateToFile($path, $this->platform, $meta, $transformed);

            if (!$this->validationEnabled) {
                return;
            }

            // Re-generate in memory solely for validation — avoids reading the file back.
            $xml = $this->generator->generate($this->platform, $meta, $transformed);
        } else {
            $xml = $this->generator->generate($this->platform, $meta, $transformed);
            if (\file_put_contents($path, $xml) === false) {
                throw new \RuntimeException(\sprintf('Cannot write feed to "%s".', $path));
            }

            if (!$this->validationEnabled) {
                return;
            }
        }

        $this->runValidation($transformed, $xml);
    }

    /**
     * Build the feed XML and return a {@see ValidationResult} without throwing.
     *
     * Useful for dry-run checks before saving or serving the feed.
     *
     * @return ValidationResult Combined validation outcome (may contain errors).
     *
     * @throws \LogicException When channel metadata has not been set.
     */
    public function validate(): ValidationResult
    {
        $meta        = $this->requireMeta();
        $transformed = $this->transformedItems();

        $xml = $this->generator->generate($this->platform, $meta, $transformed);

        return $this->mergeValidationResults($transformed, $xml);
    }

    /**
     * Build and return a {@see FeedMeta} DTO, throwing if required fields are missing.
     *
     * @return FeedMeta Populated from fluent setters.
     *
     * @throws \LogicException When `title`, `link`, or `description` is missing.
     */
    private function requireMeta(): FeedMeta
    {
        $title       = $this->title       ?? throw new \LogicException('Channel title is required.');
        $link        = $this->link        ?? throw new \LogicException('Channel link is required.');
        $description = $this->description ?? throw new \LogicException('Channel description is required.');

        return new FeedMeta($title, $link, $description, $this->language, $this->lastBuildDate);
    }

    /**
     * Apply {@see PlatformInterface::transform()} to every collected item.
     *
     * @return list<array<string, mixed>> One payload per item, ready for the generator.
     */
    private function transformedItems(): array
    {
        return \array_map(fn (FeedItem $i): array => $this->platform->transform($i), $this->feedItems);
    }

    /**
     * Run validation and either throw or log, depending on configuration.
     *
     * @param list<array<string, mixed>> $transformed Same payloads passed to the generator.
     * @param string                     $xml         Generated XML string to validate.
     *
     * @return void
     *
     * @throws ValidationException When `$validationThrow` is `true` and the result has errors.
     */
    private function runValidation(array $transformed, string $xml): void
    {
        $result = $this->mergeValidationResults($transformed, $xml);

        if ($result->isValid()) {
            return;
        }

        $this->logger->log(
            $this->validationThrow ? LogLevel::ERROR : LogLevel::WARNING,
            'XML feed validation failed.',
            ['errors' => $result->errors(), 'warnings' => $result->warnings()],
        );

        if ($this->validationThrow) {
            throw new ValidationException('Feed validation failed.', $result);
        }
    }

    /**
     * Merge built-in schema validation with the optional custom validator result.
     *
     * @param list<array<string, mixed>> $transformed Item payloads for field-level checks.
     * @param string                     $xml         Full XML document.
     *
     * @return ValidationResult Merged errors and warnings.
     */
    private function mergeValidationResults(array $transformed, string $xml): ValidationResult
    {
        $result = $this->schemaValidator->validateFull($this->platform, $transformed, $xml);

        if ($this->customValidator !== null) {
            $result = $result->merge($this->customValidator->validate($xml));
        }

        return $result;
    }

    /**
     * Assert that every key in the mapper output is declared by the platform.
     *
     * @param array<string, mixed> $mapped Single row returned by the user mapper.
     *
     * @return void
     *
     * @throws UnsupportedFieldException When an unknown key is present and {@see strictFields} is enabled.
     */
    private function assertAllowedMapperKeys(array $mapped): void
    {
        $allowed = \array_merge($this->platform->requiredFields(), $this->platform->optionalFields());
        foreach (\array_keys($mapped) as $key) {
            if (!\in_array($key, $allowed, true)) {
                throw new UnsupportedFieldException(
                    \sprintf('Field "%s" is not allowed for platform %s.', $key, $this->platform::class),
                );
            }
        }
    }
}
