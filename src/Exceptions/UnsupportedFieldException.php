<?php

declare(strict_types=1);

namespace XmlFeeds\Exceptions;

use InvalidArgumentException;

/**
 * Thrown by {@see \XmlFeeds\Builder\FeedBuilder} when `strictFields(true)` is
 * enabled and the mapper returns a key that is not declared in the platform's
 * `requiredFields()` or `optionalFields()`.
 *
 * This class defines no methods beyond those inherited from {@see InvalidArgumentException}.
 */
final class UnsupportedFieldException extends InvalidArgumentException
{
}
