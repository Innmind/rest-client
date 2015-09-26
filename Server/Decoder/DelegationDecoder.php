<?php

namespace Innmind\Rest\Client\Server\Decoder;

use Innmind\Rest\Client\Server\DecoderInterface;
use GuzzleHttp\Message\ResponseInterface;

class DelegationDecoder implements DecoderInterface
{
    protected $builders;
    protected $matches;

    public function __construct(array $builders)
    {
        $this->builders = $builders;
        $this->matches = new \SplObjectStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ResponseInterface $response)
    {
        if ($this->matches->contains($response)) {
            return true;
        }

        foreach ($this->builders as $builder) {
            if ($builder->supports($response)) {
                $this->matches->attach($response, $builder);

                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function decode(ResponseInterface $response)
    {
        if (!$this->supports($response)) {
            throw new \BadMethodCallException(
                'Trying to decode data out of an unsupported response'
            );
        }

        return $this->matches[$response]->decode($response);
    }
}
