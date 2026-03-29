<?php

declare(strict_types=1);

namespace XmlFeeds\Contracts;

/**
 * Describes the serialisation format a platform expects.
 *
 * {@see XmlGenerator} dispatches on this value to choose between
 * the RSS 2.0 and Atom 1.0 rendering paths without coupling to any
 * concrete platform class.
 */
enum FeedFormat
{
    /** RSS 2.0 (with optional namespace extensions). */
    case Rss;

    /** Atom 1.0 (`https://www.w3.org/2005/Atom`). */
    case Atom;
}
