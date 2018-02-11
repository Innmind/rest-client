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
use Innmind\Compose\{
    ContainerBuilder\ContainerBuilder,
    Loader\Yaml
};
use Innmind\Url\Path;
use Innmind\Immutable\Map;
use Innmind\HttpTransport\Transport;
use Innmind\UrlResolver\ResolverInterface;
use Innmind\Filesystem\Adapter;
use Symfony\Component\Serializer\Serializer;

$container = (new ContainerBuilder(new Yaml))(
    new Path('container.yml'),
    (new Map('string', 'mixed'))
        ->put('transport', /* instance of Transport */))
        ->put('urlResolver', /* instance of ResolverInterface */))
        ->put('serializer', /* instance of Serializer */)
        ->put('cache', /* instance of Adapter */))
);

$container
    ->get('client')
    ->server('http://example.com/')
    ->capabilities()
    ->names();
```

This example would return all the resource available through the api of `http://example.com/`.

Then you can access the following method on any server: `all`, `read`, `create`, `update` and `remove`. Check the [interface](src/Server.php) to understand how to use these methods.
