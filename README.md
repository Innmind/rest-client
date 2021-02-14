# RestClient

[![Build Status](https://github.com/Innmind/rest-client/workflows/CI/badge.svg?branch=master)](https://github.com/Innmind/rest-client/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/Innmind/rest-client/branch/develop/graph/badge.svg)](https://codecov.io/gh/Innmind/rest-client)
[![Type Coverage](https://shepherd.dev/github/Innmind/rest-client/coverage.svg)](https://shepherd.dev/github/Innmind/rest-client)


This library is intended to consume APIs built with the [`RestServer`](https://github.com/Innmind/rest-server).

## Installation

```sh
composer require innmind/rest-client
```

## Usage

```php
use Innmind\OperatingSystem\Factory;
use Innmind\Url\Path;
use Innmind\UrlResolver\UrlResolver;
use function Innmind\Rest\Client\bootstrap;

$os = Factory::build();
$client = bootstrap(
    $os->remote()->http(),
    new UrlResolver,
    $os->filesystem()->mount(Path::of('/somewhere/to/cache/data')),
);

$client
    ->server('http://example.com/')
    ->capabilities()
    ->names();
```

This example would return all the resource available through the api of `http://example.com/`.

Then you can access the following method on any server: `all`, `read`, `create`, `update` and `remove`. Check the [interface](src/Server.php) to understand how to use these methods.

## Structure

![](graph.svg)
