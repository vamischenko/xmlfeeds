<?php

declare(strict_types=1);

namespace XmlFeeds\Validator;

use DOMDocument;
use XmlFeeds\Contracts\PlatformInterface;
use XmlFeeds\Contracts\SchemaValidatorInterface;

/**
 * Built-in validator that checks field completeness, URLs, dates,
 * well-formedness, and XSD schema compliance.
 *
 * All four passes run independently and their results are merged into a single
 * {@see ValidationResult}. Any pass that finds no issues contributes an empty
 * result, so the merge is always safe.
 */
final class SchemaValidator implements SchemaValidatorInterface
{
    /**
     * Run all validation passes against the given platform, items, and XML.
     *
     * Passes (in order):
     *  1. Required-field presence
     *  2. URL format (`FILTER_VALIDATE_URL`)
     *  3. `pubDate` parseability
     *  4. Well-formed XML (libxml)
     *  5. XSD schema (when `$platform->schemaPath()` is readable)
     *
     * @param list<array<string, mixed>> $transformedItems Already-transformed item payloads.
     *
     * @return ValidationResult Merged outcome of all validation passes.
     */
    public function validateFull(
        PlatformInterface $platform,
        array $transformedItems,
        string $xml,
    ): ValidationResult {
        $r = $this->validateRequired($platform, $transformedItems);
        $r = $r->merge($this->validateUrls($transformedItems));
        $r = $r->merge($this->validateDates($transformedItems));
        $r = $r->merge($this->validateWellFormedXml($xml));

        $path = $platform->schemaPath();
        if ($path !== null && is_readable($path)) {
            $r = $r->merge($this->validateXsd($xml, $path));
        }

        return $r;
    }

    /**
     * Validate only well-formedness and (optionally) an XSD schema.
     * Useful for ad-hoc checks outside the normal build pipeline.
     *
     * @param string|null $schemaPath Absolute path to an XSD file, or null to skip XSD.
     *
     * @return ValidationResult Merged outcome of the XML parse and optional XSD pass.
     */
    public function validate(string $xml, ?string $schemaPath): ValidationResult
    {
        $r = $this->validateWellFormedXml($xml);
        if ($schemaPath !== null && is_readable($schemaPath)) {
            $r = $r->merge($this->validateXsd($xml, $schemaPath));
        }

        return $r;
    }

    /**
     * Check that every item contains all required fields with non-empty values.
     *
     * @param list<array<string, mixed>> $items
     */
    private function validateRequired(PlatformInterface $platform, array $items): ValidationResult
    {
        $required = $platform->requiredFields();
        $errors = [];

        foreach ($items as $idx => $row) {
            foreach ($required as $field) {
                if (!\array_key_exists($field, $row) || $row[$field] === null || $row[$field] === '') {
                    $errors[] = "Item #{$idx}: missing required field \"{$field}\".";
                }
            }
        }

        return new ValidationResult($errors, []);
    }

    /**
     * Validate that each `link` value is a well-formed URL.
     *
     * @param list<array<string, mixed>> $items
     */
    private function validateUrls(array $items): ValidationResult
    {
        $errors = [];
        foreach ($items as $idx => $row) {
            if (isset($row['link']) && \is_string($row['link'])) {
                if (\filter_var($row['link'], \FILTER_VALIDATE_URL) === false) {
                    $errors[] = "Item #{$idx}: invalid URL in \"link\".";
                }
            }
        }

        return new ValidationResult($errors, []);
    }

    /**
     * Validate that each `pubDate` value can be parsed as a date/time.
     *
     * Accepts {@see \DateTimeInterface} instances directly. String values are
     * parsed via {@see \DateTimeImmutable}; parse failures are caught as {@see \Exception}.
     *
     * @param list<array<string, mixed>> $items
     */
    private function validateDates(array $items): ValidationResult
    {
        $errors = [];
        foreach ($items as $idx => $row) {
            if (!isset($row['pubDate'])) {
                continue;
            }
            $d = $row['pubDate'];
            if ($d instanceof \DateTimeInterface) {
                continue;
            }
            try {
                new \DateTimeImmutable((string) $d);
            } catch (\Exception) {
                $errors[] = "Item #{$idx}: cannot parse \"pubDate\".";
            }
        }

        return new ValidationResult($errors, []);
    }

    /**
     * Check that the XML string is well-formed using libxml.
     */
    private function validateWellFormedXml(string $xml): ValidationResult
    {
        $prev = \libxml_use_internal_errors(true);
        \libxml_clear_errors();
        $doc = new DOMDocument();
        $ok  = $doc->loadXML($xml);
        $errs = \libxml_get_errors();
        \libxml_clear_errors();
        \libxml_use_internal_errors($prev);

        if ($ok) {
            return new ValidationResult([], []);
        }

        $messages = [];
        foreach ($errs as $e) {
            $messages[] = \trim($e->message);
        }

        return new ValidationResult($messages ?: ['Malformed XML.'], []);
    }

    /**
     * Validate the XML document against an XSD schema file.
     *
     * @param string $schemaPath Absolute path to a readable `.xsd` file.
     */
    private function validateXsd(string $xml, string $schemaPath): ValidationResult
    {
        $prev = \libxml_use_internal_errors(true);
        \libxml_clear_errors();
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $ok  = $doc->schemaValidate($schemaPath);
        $errs = \libxml_get_errors();
        \libxml_clear_errors();
        \libxml_use_internal_errors($prev);

        if ($ok) {
            return new ValidationResult([], []);
        }

        $messages = [];
        foreach ($errs as $e) {
            $messages[] = \trim($e->message);
        }

        return new ValidationResult($messages ?: ['XSD validation failed.'], []);
    }
}
