# xml-feeds

PHP **8.2+** библиотека для генерации XML-фидов (RSS 2.0, Atom 1.0) под новостные агрегаторы и социальные сети: Яндекс, Google News, Рамблер, Mail.ru, Telegram, ВКонтакте, Facebook и др. Без привязки к фреймворку, Fluent API, опциональная валидация (поля + XSD).

## Установка

```bash
composer require xmlfeeds/xml-feeds
```

Требования: PHP 8.2+, `ext-dom`, `ext-xmlwriter`, `ext-libxml`.

## Быстрый старт

```php
use XmlFeeds\Builder\FeedBuilder;
use XmlFeeds\Platforms\YandexNews;

$xml = FeedBuilder::for(new YandexNews())
    ->title('Мой сайт')
    ->link('https://example.com')
    ->description('Новости')
    ->items($articles, fn ($a) => [
        'title'   => $a->title,
        'link'    => $a->url,
        'pubDate' => $a->published_at,
        'content' => $a->body,
    ])
    ->withValidation()
    ->build();
```

`build()` возвращает строку с XML (`UTF-8`, декларация `<?xml version="1.0" encoding="UTF-8"?>`).

## Платформы

### Новостные агрегаторы

| Класс | Назначение | Формат | Расширения |
| ----- | ---------- | ------ | ---------- |
| `XmlFeeds\Platforms\RssFeed` | Чистый RSS 2.0 | RSS 2.0 | — |
| `XmlFeeds\Platforms\AtomFeed` | Atom 1.0 | Atom 1.0 | — |
| `XmlFeeds\Platforms\YandexNews` | Яндекс.Новости | RSS 2.0 | `yandex:*`, `content:encoded` |
| `XmlFeeds\Platforms\YandexZen` | Яндекс Дзен | RSS 2.0 | `media:*`, `content:encoded` |
| `XmlFeeds\Platforms\YandexTurbo` | Яндекс Турбо | RSS 2.0 | `turbo:*`, `content:encoded` |
| `XmlFeeds\Platforms\GoogleNews` | Google News | RSS 2.0 | `news:*`, `content:encoded` |
| `XmlFeeds\Platforms\RamblerNews` | Рамблер/Новости | RSS 2.0 | `content:encoded` |
| `XmlFeeds\Platforms\MailRuNews` | Mail.ru Новости | RSS 2.0 | `content:encoded` |

### Социальные сети и мессенджеры

| Класс | Назначение | Особенности |
| ----- | ---------- | ----------- |
| `XmlFeeds\Platforms\TelegramChannel` | Telegram RSS-боты | Заголовок ≤ 255 символов (лимит caption) |
| `XmlFeeds\Platforms\VkontakteFeed` | ВКонтакте / SMM-сервисы | Стандартный RSS для автопостинга |
| `XmlFeeds\Platforms\FacebookFeed` | Facebook Instant Articles | `content` обязателен (требование IA-формата) |

Канал задаётся через `title`, `link`, `description`; опционально `language` (по умолчанию `ru`), `lastBuildDate`.

## Поля элементов (маппер)

| Поле | Тип | Описание |
| ---- | --- | -------- |
| `title` | `string` | Заголовок, обязательное |
| `link` | `string` | Полный URL статьи, обязательное |
| `pubDate` | `\DateTimeInterface\|string` | Дата публикации |
| `description` | `string` | Краткое описание / лид |
| `content` | `string` | Полный HTML-текст (CDATA) |
| `author` | `string` | Автор |
| `category` | `string\|list<string>` | Рубрика (одна или несколько) |
| `enclosure` | `array{url, type, length}` | Медиавложение |
| `keywords` | `string\|list<string>` | Ключевые слова |
| `guid` | `string` | Уникальный идентификатор |
| `source` | `string` | Источник (Яндекс.Новости) |
| `genre` | `string` | Жанр (Яндекс.Новости) |
| `turboContent` | `string` | HTML Турбо-страницы (Яндекс Турбо) |
| `publication_name` | `string` | Название издания (Google News) |
| `publication_language` | `string` | Язык издания (Google News) |
| `media:content` | `array{url, type, medium}` | Медиа (Яндекс Дзен) |
| `media:thumbnail` | `array{url}` | Превью (Яндекс Дзен) |

Полный список разрешённых полей — `requiredFields()` + `optionalFields()` у выбранной платформы.

## Валидация

```php
// Бросает ValidationException при первой ошибке (по умолчанию)
$builder->withValidation(throw: true)->build();

// Логирует ошибки, XML всё равно возвращается
$builder->withValidation(throw: false)->build();

// Только генерация, без валидации
$builder->withoutValidation()->build();

// Dry-run: получить ValidationResult без исключений
$result = $builder->validate();
if (!$result->isValid()) {
    print_r($result->errors());
}
```

Что проверяется:

- Наличие всех обязательных полей
- Корректность URL (`FILTER_VALIDATE_URL`)
- Парсируемость `pubDate`
- Well-formed XML (libxml)
- XSD-схема (если платформа предоставляет `schemaPath()`)

## Социальные сети — примеры

### Telegram

```php
use XmlFeeds\Builder\FeedBuilder;
use XmlFeeds\Platforms\TelegramChannel;

$xml = FeedBuilder::for(new TelegramChannel())
    ->title('Мой канал')
    ->link('https://t.me/mychannel')
    ->description('Лучшие новости')
    ->items($posts, fn ($p) => [
        'title'   => $p->title,    // обрежется до 255 символов
        'link'    => $p->url,
        'pubDate' => $p->created_at,
        'content' => $p->html,     // → content:encoded (CDATA)
        'enclosure' => ['url' => $p->image_url, 'type' => 'image/jpeg', 'length' => 0],
    ])
    ->build();
```

### ВКонтакте

```php
use XmlFeeds\Platforms\VkontakteFeed;

$xml = FeedBuilder::for(new VkontakteFeed())
    ->title('Новости')
    ->link('https://example.com')
    ->description('Последние публикации')
    ->items($articles, fn ($a) => [
        'title'   => $a->title,
        'link'    => $a->url,
        'pubDate' => $a->published_at,
        'content' => $a->body,
    ])
    ->build();
```

### Facebook Instant Articles

```php
use XmlFeeds\Platforms\FacebookFeed;

$xml = FeedBuilder::for(new FacebookFeed())
    ->title('My Publication')
    ->link('https://example.com')
    ->description('Latest articles')
    ->items($articles, fn ($a) => [
        'title'   => $a->title,
        'link'    => $a->url,
        'pubDate' => $a->published_at,
        'content' => $a->html_body,  // обязательное поле для Instant Articles
    ])
    ->build();
```

## Дополнительные возможности

**Запись в файл** (для больших фидов `XmlGenerator` пишет через `XMLWriter::openUri` без буферизации):

```php
FeedBuilder::for(new RssFeed())
    // ...
    ->withoutValidation()
    ->buildToFile('/var/www/feeds/news.xml');
```

**Свой генератор** — реализуйте `XmlFeeds\Contracts\GeneratorInterface`
(и `StreamableGeneratorInterface` для потоковой записи в файл):

```php
$builder->generator($myGenerator);
```

**Свой валидатор схемы** — реализуйте `XmlFeeds\Contracts\SchemaValidatorInterface`:

```php
$builder->schemaValidator($mySchemaValidator);
```

**Дополнительный валидатор** (запускается после встроенного) — реализуйте `XmlFeeds\Contracts\ValidatorInterface`:

```php
$builder->validator($myExtraValidator);
```

**Строгий список полей** — запрещает ключи, не объявленные платформой:

```php
$builder->strictFields(true);
```

**Логирование** (PSR-3): при ошибке валидации пишется уровень `error` (throw=true) или `warning` (throw=false):

```php
$builder->logger($psrLogger);
```

**Новая платформа** — реализуйте `PlatformInterface` (или расширьте `AbstractRssPlatform`):

```php
final class MyPlatform extends AbstractRssPlatform
{
    public function namespace(): array { return []; }
    public function requiredFields(): array { return ['title', 'link']; }
    public function optionalFields(): array { return ['description', 'pubDate']; }
    public function schemaPath(): ?string { return null; }
    public function transform(FeedItem $item): array { return $item->toArray(); }
}
```

## Разработка

```bash
composer install
composer test        # PHPUnit 12
composer phpstan     # статический анализ (PHPStan 2)
composer cs-fix      # PHP CS Fixer (PSR-12)
composer benchmark   # нагрузочный прогон (по умолчанию 1000 элементов)
```

Параметр бенчмарка: `php tools/benchmark.php 5000`.

## Лицензия

MIT

---

## English

PHP **8.2+** library for generating XML feeds (RSS 2.0, Atom 1.0) for news aggregators and social networks: Yandex, Google News, Rambler, Mail.ru, Telegram, VKontakte, Facebook, and others. Framework-agnostic, Fluent API, optional validation (field rules + XSD).

### Installation

```bash
composer require xmlfeeds/xml-feeds
```

Requirements: PHP 8.2+, `ext-dom`, `ext-xmlwriter`, `ext-libxml`.

### Quick start

```php
use XmlFeeds\Builder\FeedBuilder;
use XmlFeeds\Platforms\YandexNews;

$xml = FeedBuilder::for(new YandexNews())
    ->title('My site')
    ->link('https://example.com')
    ->description('News')
    ->items($articles, fn ($a) => [
        'title'   => $a->title,
        'link'    => $a->url,
        'pubDate' => $a->published_at,
        'content' => $a->body,
    ])
    ->withValidation()
    ->build();
```

### Platforms

#### News aggregators

| Class | Purpose | Format | Extensions |
| ----- | ------- | ------ | ---------- |
| `XmlFeeds\Platforms\RssFeed` | Plain RSS 2.0 | RSS 2.0 | — |
| `XmlFeeds\Platforms\AtomFeed` | Atom 1.0 | Atom 1.0 | — |
| `XmlFeeds\Platforms\YandexNews` | Yandex News | RSS 2.0 | `yandex:*`, `content:encoded` |
| `XmlFeeds\Platforms\YandexZen` | Yandex Zen | RSS 2.0 | `media:*`, `content:encoded` |
| `XmlFeeds\Platforms\YandexTurbo` | Yandex Turbo | RSS 2.0 | `turbo:*`, `content:encoded` |
| `XmlFeeds\Platforms\GoogleNews` | Google News | RSS 2.0 | `news:*`, `content:encoded` |
| `XmlFeeds\Platforms\RamblerNews` | Rambler | RSS 2.0 | `content:encoded` |
| `XmlFeeds\Platforms\MailRuNews` | Mail.ru | RSS 2.0 | `content:encoded` |

#### Social networks & messengers

| Class | Purpose | Notes |
| ----- | ------- | ----- |
| `XmlFeeds\Platforms\TelegramChannel` | Telegram RSS bots | Title truncated to 255 chars |
| `XmlFeeds\Platforms\VkontakteFeed` | VKontakte / SMM tools | Standard RSS autopublishing |
| `XmlFeeds\Platforms\FacebookFeed` | Facebook Instant Articles | `content` field is required |

### Validation

```php
$builder->withValidation(throw: true)->build();  // throws ValidationException on error
$builder->withValidation(throw: false)->build(); // logs errors, returns XML anyway
$builder->withoutValidation()->build();           // skip validation entirely

$result = $builder->validate(); // dry-run, returns ValidationResult
```

### Extending

- **Custom platform**: implement `PlatformInterface` (or extend `AbstractRssPlatform`)
- **Custom generator**: implement `GeneratorInterface` (+ `StreamableGeneratorInterface` for file output)
- **Custom schema validator**: implement `SchemaValidatorInterface`
- **Extra validator**: implement `ValidatorInterface`

### Development

```bash
composer install
composer test        # PHPUnit 12
composer phpstan     # static analysis (PHPStan 2)
composer cs-fix      # PHP CS Fixer (PSR-12)
composer benchmark   # load test (default: 1000 items)
```

### License

MIT
