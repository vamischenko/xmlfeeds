<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use XmlFeeds\Builder\FeedBuilder;
use XmlFeeds\Platforms\RssFeed;

$n = (int) ($argv[1] ?? 1000);
if ($n < 1) {
    fwrite(STDERR, "Usage: php tools/benchmark.php [count]\n");
    exit(1);
}

$items = [];
for ($i = 0; $i < $n; ++$i) {
    $items[] = [
        'title' => 'Article '.$i,
        'link' => 'https://example.com/p/'.$i,
        'pubDate' => new \DateTimeImmutable('@1700000000'),
        'description' => 'Lead text '.$i,
    ];
}

$start = hrtime(true);
$mem = memory_get_usage(true);

$xml = FeedBuilder::for(new RssFeed())
    ->title('Bench')
    ->link('https://example.com')
    ->description('D')
    ->items($items, static fn (array $a): array => $a)
    ->withoutValidation()
    ->build();

$elapsedMs = (hrtime(true) - $start) / 1_000_000;
$peak = memory_get_peak_usage(true);

printf(
    "Items: %d\nTime: %.2f ms\nPeak memory: %.2f MiB\nXML length: %d bytes\n",
    $n,
    $elapsedMs,
    $peak / 1024 / 1024,
    \strlen($xml),
);
