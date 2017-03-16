<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\{
    Serializer\Normalizer\IdentityNormalizer,
    IdentityInterface,
    Visitor\ResolveIdentity,
    Definition\HttpResource,
    Definition\Identity,
    Definition\Property
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
use Innmind\UrlResolver\UrlResolver;
use Innmind\Immutable\{
    Map,
    Set,
    SetInterface
};
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use PHPUnit\Framework\TestCase;

class IdentityNormalizerTest extends TestCase
{
    private $normalizer;
    private $definition;

    public function setUp()
    {
        $this->normalizer = new IdentityNormalizer(
            new ResolveIdentity(
                new UrlResolver
            )
        );
        $this->definition = new HttpResource(
            'foo',
            Url::fromString('http://example.com/foo'),
            new Identity('uuid'),
            new Map('string', Property::class),
            new Map('scalar', 'variable'),
            new Map('string', 'string'),
            false
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            DenormalizerInterface::class,
            $this->normalizer
        );
    }

    public function testSupportsDenormalization()
    {
        $this->assertTrue(
            $this->normalizer->supportsDenormalization(
                $this->createMock(ResponseInterface::class),
                'rest_identity'
            )
        );
        $this->assertFalse(
            $this->normalizer->supportsDenormalization(
                new \stdClass,
                'rest_identity'
            )
        );
        $this->assertFalse(
            $this->normalizer->supportsDenormalization(
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
        $this->normalizer->denormalize(
            new \stdClass,
            'rest_identity',
            null,
            ['definition' => $this->definition]
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingInvalidType()
    {
        $this->normalizer->denormalize(
            $this->createMock(ResponseInterface::class),
            'identity',
            null,
            ['definition' => $this->definition]
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingWhithoutDefinition()
    {
        $this->normalizer->denormalize(
            $this->createMock(ResponseInterface::class),
            'identity'
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingWhithInvalidDefinition()
    {
        $this->normalizer->denormalize(
            $this->createMock(ResponseInterface::class),
            'identity',
            null,
            ['definition' => []]
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

        $this->normalizer->denormalize(
            $response,
            'rest_identity',
            null,
            ['definition' => $this->definition]
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

        $this->normalizer->denormalize(
            $response,
            'rest_identity',
            null,
            ['definition' => $this->definition]
        );
    }

    public function testDenormalize()
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
                            new Location(
                                new LocationValue(
                                    Url::fromString('http://example.com/foo/42')
                                )
                            )
                        )
                )
            );

        $identity = $this->normalizer->denormalize(
            $response,
            'rest_identity',
            null,
            ['definition' => $this->definition]
        );

        $this->assertInstanceOf(IdentityInterface::class, $identity);
        $this->assertSame('42', (string) $identity);
    }
}
