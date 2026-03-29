<?php

declare(strict_types=1);

namespace XmlFeeds\Tests;

use PHPUnit\Framework\TestCase;
use XmlFeeds\Generator\CdataWrapper;
use XmlFeeds\Generator\XmlDate;

final class CdataAndDateTest extends TestCase
{
    public function testCdataHeuristic(): void
    {
        self::assertTrue(CdataWrapper::prefersCdata('<p>x</p>'));
        self::assertFalse(CdataWrapper::prefersCdata('plain'));
    }

    public function testRfc2822Format(): void
    {
        $s = XmlDate::toRfc2822(new \DateTimeImmutable('2026-03-29 12:00:00 +0000'));
        self::assertMatchesRegularExpression('/^[A-Za-z]{3},/', $s);
    }
}
