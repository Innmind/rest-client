# RestClient

| `master` | `develop` |
|----------|-----------|
| [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/rest-client/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Innmind/rest-client/?branch=master) | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/rest-client/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/rest-client/?branch=develop) |
| [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/rest-client/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Innmind/rest-client/?branch=master) | [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/rest-client/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/rest-client/?branch=develop) |
| [![Build Status](https://scrutinizer-ci.com/g/Innmind/rest-client/badges/build.png?b=master)](https://scrutinizer-ci.com/g/Innmind/rest-client/build-status/master) | [![Build Status](https://scrutinizer-ci.com/g/Innmind/rest-client/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/rest-client/build-status/develop) |


This library is intended to consume APIs built with the [`RestServer`](https://github.com/Innmind/rest-server).

## Installation

```sh
composer require innmind/rest-client
```

## Usage

```php
use function Innmind\Rest\Client\bootstrap;

$client = bootstrap(
    /* instance of Innmind\HttpTransport\Transport */,
    /* instance of Innmind\UrlResolver\ResolverInterface */,
    /* instance of Innmind\Filesystem\Adapter */
);

$client
    ->server('http://example.com/')
    ->capabilities()
    ->names();
```

This example would return all the resource available through the api of `http://example.com/`.

Then you can access the following method on any server: `all`, `read`, `create`, `update` and `remove`. Check the [interface](src/Server.php) to understand how to use these methods.
