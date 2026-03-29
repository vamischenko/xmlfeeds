<?php

declare(strict_types=1);

namespace XmlFeeds\Validator;

/**
 * Immutable value object that holds the outcome of a validation run.
 *
 * A result is considered **valid** when its `$errors` list is empty.
 * Warnings are informational and do not affect validity.
 *
 * Multiple results can be combined with {@see merge()} — useful when
 * several validation passes run independently and their findings need
 * to be aggregated before reporting.
 */
final readonly class ValidationResult
{
    /**
     * @param list<string> $errors   Blocking issues; any non-empty list makes the result invalid.
     * @param list<string> $warnings Non-blocking informational messages.
     */
    public function __construct(
        private array $errors = [],
        private array $warnings = [],
    ) {
    }

    /**
     * Return `true` when there are no errors.
     *
     * @return bool `true` if {@see errors()} is empty; warnings are ignored for validity.
     */
    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /**
     * Return the list of blocking error messages.
     *
     * @return list<string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Return the list of non-blocking warning messages.
     *
     * @return list<string>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /**
     * Combine this result with another, concatenating both error and warning lists.
     *
     * Neither `$this` nor `$other` is modified; a new instance is returned.
     *
     * @param self $other Result from another validation pass to append.
     *
     * @return self New instance containing merged errors and warnings.
     */
    public function merge(self $other): self
    {
        return new self(
            \array_merge($this->errors, $other->errors),
            \array_merge($this->warnings, $other->warnings),
        );
    }
}
