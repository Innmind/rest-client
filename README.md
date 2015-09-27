# RestClient

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/rest-client/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/rest-client/?branch=develop)
[![Code Coverage](https://scrutinizer-ci.com/g/Innmind/rest-client/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/rest-client/?branch=develop)
[![Build Status](https://scrutinizer-ci.com/g/Innmind/rest-client/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/rest-client/build-status/develop)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/cc996425-8db6-4b92-98e7-ec38f65d643e/big.png)](https://insight.sensiolabs.com/projects/cc996425-8db6-4b92-98e7-ec38f65d643e)

This library is intended to consume APIs built with the [`RestServer`](https://github.com/Innmind/rest-server).

## Installation

```sh
composer require innmind/rest-client
```

## Usage

### The client

Here's the snippet to create an instance of the client:

```php
use Innmind\Rest\Client\Client;
use Innmind\Rest\Client\Validator;
use Innmind\Rest\Client\Serializer\Normalizer\CollectionNormalizer;
use Innmind\Rest\Client\Serializer\Normalizer\ResourceNormalizer;
use Innmind\Rest\Client\Serializer\Encoder\ResponseEncoder;
use Innmind\Rest\Client\Server\Decoder\DelegationDecoder;
use Innmind\Rest\Client\Server\Decoder\CollectionDecoder;
use Innmind\Rest\Client\Server\Decoder\ResourceDecoder;
use Innmind\Rest\Client\Definition\Loader;
use Innmind\Rest\Client\Cache\FileCache;
use Innmind\UrlResolver\UrlResolver;
use GuzzleHttp\Client as Http;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Validator\Validation;
use Symfony\Component\EventDispatcher\EventDispatcher;

$resolver = new UrlResolver([]);
$http = new Http;

$client = new Client(
    new Loader(
        new FileCache('/path/where/to/cache/resource/definition.php'),
        $resolver,
        null,
        $http
    ),
    new Serializer(
        [new CollectionNormalizer, new ResourceNormalizer],
        [
            new ResponseEncoder(new DelegationDecoder([
                new CollectionDecoder($resolver),
                new ResourceDecoder($resolver),
            ])),
            new JsonEncoder,
        ]
    ),
    $resolver,
    new Validator(Validation::createValidator(), new ResourceNormalizer),
    new EventDispatcher,
    $http
);
```

### Loading resources

When *reading* a folder it will return a `Collection` object; but by default it only keeps the links to the resources so they're loaded only when iterating over the collection.

**Note**: the collection supports the pagination by reading the `Link` with the `rel` set to `next`

```php
$resources = $client->read('http://example.com/collection/resource/');
```

To read a specific resource:

```php
$resource = $client->read('http://example.com/collection/resource/42');
```

### Creating a resource

```php
use Innmind\Rest\Client\Resource;

$resource = new Resource;
$resource->set('some', 'property');
$client->create('http://example.com/collection/resource/', $resource);
```

**Note**: in case the resource creation fails, if the definition is not fresh (meaning not loaded from the API in the current process) it will refresh the definition and validate your resource before sending it back to the API (an exception is thrown if the validation fails, saving a roundtrip to the API).

### Updating a resource

```php
use Innmind\Rest\Client\Resource;

$resource = new Resource;
$resource->set('some', 'property');
$client->update('http://example.com/collection/resource/42', $resource);
```

**Note**: it follows the same behaviour as `create` in case of failure.

### Deleting a resource

```php
$client->remove('http://example.com/collection/resource/42');
```
