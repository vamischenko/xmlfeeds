<?php

declare(strict_types=1);

namespace XmlFeeds\Contracts;

use XmlFeeds\Validator\ValidationResult;

/**
 * An additional, user-supplied XML validator that runs after the built-in
 * {@see SchemaValidatorInterface} pass.
 *
 * Attach a custom validator to a builder via
 * {@see \XmlFeeds\Builder\FeedBuilder::validator()}.
 *
 * Typical use cases:
 *  - Business-rule checks (e.g. minimum item count, image dimensions)
 *  - Platform-specific constraints not covered by XSD
 *  - Integration with external validation services
 */
interface ValidatorInterface
{
    /**
     * Validate the fully generated XML string and return the result.
     *
     * The method must never throw; all findings should be expressed as
     * errors or warnings inside the returned {@see ValidationResult}.
     *
     * @return ValidationResult Must not be discarded by callers that need to inspect findings.
     */
    public function validate(string $xml): ValidationResult;
}
