<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\{
    Serializer\Normalizer\IdentityNormalizer,
    IdentityInterface
};
use Innmind\Http\{
    Message\ResponseInterface,
    Headers,
    Header\HeaderInterface,
    Header\HeaderValueInterface,
    Header\Header,
    Header\HeaderValue,
    Header\Location,
    Header\LocationValue,
    Header\ParameterInterface
};
use Innmind\Url\Url;
use Innmind\Immutable\{
    Map,
    Set,
    SetInterface
};
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class IdentityNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            DenormalizerInterface::class,
            new IdentityNormalizer
        );
    }

    public function testSupportsDenormalization()
    {
        $normalizer = new IdentityNormalizer;

        $this->assertTrue(
            $normalizer->supportsDenormalization(
                $this->createMock(ResponseInterface::class),
                'rest_identity'
            )
        );
        $this->assertFalse(
            $normalizer->supportsDenormalization(
                new \stdClass,
                'rest_identity'
            )
        );
        $this->assertFalse(
            $normalizer->supportsDenormalization(
                $this->createMock(ResponseInterface::class),
                'identity'
            )
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingInvalidData()
    {
        (new IdentityNormalizer)->denormalize(
            new \stdClass,
            'rest_identity'
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingInvalidType()
    {
        (new IdentityNormalizer)->denormalize(
            $this->createMock(ResponseInterface::class),
            'identity'
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\IdentityNotFoundException
     */
    public function testThrowWhenDenormalizingWithoutLocation()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers(
                    new Map('string', HeaderInterface::class)
                )
            );

        (new IdentityNormalizer)->denormalize(
            $response,
            'rest_identity'
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\IdentityNotFoundException
     */
    public function testThrowWhenDenormalizingWithUnsupportedLocation()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers(
                    (new Map('string', HeaderInterface::class))
                        ->put(
                            'Location',
                            new Header(
                                'Location',
                                (new Set(HeaderValueInterface::class))
                                    ->add(new HeaderValue('http://example.com/foo/42'))
                            )
                        )
                )
            );

        (new IdentityNormalizer)->denormalize(
            $response,
            'rest_identity'
        );
    }

    public function testDenormalize()
    {
        $normalizer = new IdentityNormalizer;
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers(
                    (new Map('string', HeaderInterface::class))
                        ->put(
                            'Location',
                            new Location(
                                new LocationValue(
                                    Url::fromString('http://example.com/foo/42')
                                )
                            )
                        )
                )
            );

        $identity = $normalizer->denormalize($response, 'rest_identity');

        $this->assertInstanceOf(IdentityInterface::class, $identity);
        $this->assertSame('42', (string) $identity);
    }
}
