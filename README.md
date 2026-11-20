# Warface SDK (PHP)

A lightweight client for the Warface API. It requires PHP 8.1 or newer, a
PSR-18 HTTP client and a PSR-17 HTTP factory. HTTP discovery and HTTPlug are
used to build the default client.

## Installation

Install the SDK and a PSR-18 client and PSR-17 factory. For example, Guzzle 7
provides both:

```bash
composer require hyperplural/warface-sdk guzzlehttp/guzzle:^7
```

## Quickstart

```php
<?php

use Hyperplural\WarfaceSdk\Client;
use Hyperplural\WarfaceSdk\Enum\GameClass;
use Hyperplural\WarfaceSdk\Enum\RatingLeague;

$client = new Client();

$stats = $client->user()->stat('Nickname');
$playerAchievements = $client->user()->achievements('Nickname');
$achievementCatalog = $client->achievement()->catalog();
$clanMembers = $client->clan()->members('ClanName');
$missions = $client->game()->missions();
$clanRating = $client->rating()->clan();
$monthlyRating = $client->rating()->monthly(null, RatingLeague::ELITE, 0);
$top100 = $client->rating()->top100(GameClass::MEDIC);
```

Results are PHP arrays decoded from API JSON objects or arrays. The SDK does
not impose a schema on the remote API response. Negative monthly rating page
numbers are rejected before a request is sent.

## HTTP client and timeouts

`Client::createWithHttpClient()` accepts any PSR-18 client. PSR-18 does not
define timeout configuration; set connection and request timeouts on the
chosen implementation. For example, with Guzzle:

```php
<?php

use GuzzleHttp\Client as HttpClient;
use Hyperplural\WarfaceSdk\Client;

$http = new HttpClient([
    'connect_timeout' => 3.0,
    'timeout' => 10.0,
]);
$client = Client::createWithHttpClient($http);
```

The defaults of a discovered HTTP client depend on its implementation. For
more control, pass a `ClientBuilder` with a PSR-18 client and, optionally, a
PSR-17 request factory to `new Client($builder)`. Plugins added to the builder
run in their configured order before the SDK's default plugins. Configure any
retry policy in the transport or a custom plugin: the SDK does not retry by
default. Keep attempts bounded, respect `Retry-After`, and use backoff and
jitter where appropriate.

## Errors

All SDK exceptions extend `WarfaceApiException`. HTTP 400 and 404 produce
`BadRequestException` and `NotFoundException`; HTTP 429 produces
`RateLimitException`; HTTP 500 produces `InternalServerErrorException`, and
other 5xx responses produce `ServerException`. Transport failures produce
`TransportException` with the original exception available through
`getPrevious()`. Malformed JSON produces `ApiResponseErrorException` with the
original `JsonException` as its cause. Other unexpected JSON structures and
statuses also produce `ApiResponseErrorException`.

Passing a negative page to `rating()->monthly()` throws PHP's
`InvalidArgumentException` before sending a request. This validation exception
does not extend `WarfaceApiException` and must be handled separately.

```php
<?php

use Hyperplural\WarfaceSdk\Client;
use Hyperplural\WarfaceSdk\Exception\RateLimitException;
use Hyperplural\WarfaceSdk\Exception\WarfaceApiException;

$client = new Client();

try {
    $stats = $client->user()->stat('Nickname');
} catch (RateLimitException $exception) {
    $retryAfter = $exception->getRetryAfter(); // Header value or null.
    // Apply a bounded retry policy appropriate for your application.
} catch (WarfaceApiException $exception) {
    // Handle the failure in your application.
}
```

## Warface rate-limit workaround

The SDK applies `RateLimitWorkaroundPlugin` by default to every API request.
It preserves an existing workaround for repeated identical requests, which
may otherwise wait for a long time or time out. It appends `<` followed by
64 random hexadecimal characters to a non-empty `name` value, except `"0"`.
When `name` is absent, empty, `"0"`, or not a string, the parameter is set to
the suffix alone. It parses and rebuilds the query using PHP's `parse_str()`
and RFC 3986 encoding, so PHP's rules for repeated and transformed parameter
names apply. This also applies to endpoints that have no `name` parameter of
their own. The workaround describes existing SDK behavior and does not imply a
guarantee about how the Warface server handles requests.

The old `BypassTimeoutResponsePlugin` name remains available for compatibility
and is deprecated in favor of `RateLimitWorkaroundPlugin`.

## Testing

Unit tests use fixtures and a mock HTTP client; they do not call Warface API.
Run the checks locally with Composer or with Docker and Compose v2:

```bash
composer validate --strict
composer audit
composer run test:types
composer run test:cs
composer run test:unit
composer run rector:dry
composer run test:coverage
composer run coverage:check

# Alternatively, use the container for individual commands:
./docker/test.sh composer run test:unit
./docker/test.sh composer run test:coverage
./docker/test.sh composer run coverage:check
```

The container installs dependencies in a Docker volume and includes PCOV for
coverage. CI enforces the existing 100% line-coverage threshold.

## Compatibility and releases

The SDK uses semantic versioning. Consult release notes before upgrading across
a major version. Stricter response validation, new exception types and rejected
negative pages can affect applications that relied on previous behavior.
Responses and server-side limits remain subject to changes in Warface API.
For API details, see the [Warface API documentation](https://ru.warface.com/wiki/index.php/API).

## License

MIT. See LICENSE for details.
