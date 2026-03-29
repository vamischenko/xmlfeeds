<?php

declare(strict_types=1);

namespace XmlFeeds\Contracts;

use XmlFeeds\Validator\ValidationResult;

/**
 * Validates a fully built XML string against structural rules and XSD schema.
 *
 * Implementations receive the platform (to resolve required fields and schema
 * path) and the already-transformed item payloads so they can run both
 * field-level and document-level checks.
 *
 * The built-in implementation is {@see \XmlFeeds\Validator\SchemaValidator}.
 */
interface SchemaValidatorInterface
{
    /**
     * Run all validation passes: required fields, URLs, dates, well-formed XML,
     * and XSD schema (when the platform provides a schema path).
     *
     * @param list<array<string, mixed>> $transformedItems Already-transformed item payloads.
     *
     * @return ValidationResult Aggregated errors and warnings from every pass.
     */
    public function validateFull(
        PlatformInterface $platform,
        array $transformedItems,
        string $xml,
    ): ValidationResult;
}
