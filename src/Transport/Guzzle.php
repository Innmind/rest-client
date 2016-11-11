<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Transport;

use Innmind\Rest\Client\TransportInterface;
use Innmind\Http\{
    Message\RequestInterface,
    Message\ResponseInterface,
    Translator\Response\Psr7Translator,
    Header\HeaderValueInterface
};
use GuzzleHttp\ClientInterface;

final class Guzzle implements TransportInterface
{
    private $client;
    private $translator;

    public function __construct(
        ClientInterface $client,
        Psr7Translator $translator
    ) {
        $this->client = $client;
        $this->translator = $translator;
    }

    public function fulfill(RequestInterface $request): ResponseInterface
    {
        $options = [];
        $headers = [];

        foreach ($request->headers() as $header) {
            $headers[$header->name()] = $header
                ->values()
                ->reduce(
                    [],
                    function(array $raw, HeaderValueInterface $value): array {
                        $raw[] = (string) $value;

                        return $raw;
                    }
                );
        }

        if (count($headers) > 0) {
            $options['headers'] = $headers;
        }

        if ($request->body()->size() > 0) {
            $options['body'] = (string) $request->body();
        }

        $response = $this->client->request(
            (string) $request->method(),
            (string) $request->url(),
            $options
        );

        return $this->translator->translate($response);
    }
}
