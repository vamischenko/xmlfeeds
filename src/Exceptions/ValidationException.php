<?php

declare(strict_types=1);

namespace XmlFeeds\Exceptions;

use RuntimeException;
use XmlFeeds\Validator\ValidationResult;

/**
 * Thrown by {@see \XmlFeeds\Builder\FeedBuilder::build()} when validation is
 * enabled with `$throw = true` and the generated feed fails validation.
 *
 * The full {@see ValidationResult} (errors and warnings) is available via
 * {@see getResult()} for detailed error reporting.
 */
final class ValidationException extends RuntimeException
{
    /**
     * @param string            $message  Short human-readable reason (e.g. "Feed validation failed.").
     * @param ValidationResult  $result   Full error and warning lists from the failed validation run.
     * @param int               $code     Optional exception code (default `0`).
     * @param \Throwable|null   $previous Optional wrapped exception.
     */
    public function __construct(
        string $message,
        private readonly ValidationResult $result,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Return the validation result that caused this exception.
     *
     * @return ValidationResult Same instance passed to the constructor.
     */
    public function getResult(): ValidationResult
    {
        return $this->result;
    }
}
