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
    Message\Response,
    Headers\Headers,
    Header,
    Header\Value\Value,
    Header\HeaderValue,
    Header\Location,
    Header\LocationValue
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
                $this->createMock(Response::class),
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
                $this->createMock(Response::class),
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
            $this->createMock(Response::class),
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
            $this->createMock(Response::class),
            'identity'
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingWhithInvalidDefinition()
    {
        $this->normalizer->denormalize(
            $this->createMock(Response::class),
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
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers
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
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers(
                    (new Map('string', Header::class))
                        ->put(
                            'Location',
                            new Header\Header(
                                'Location',
                                new Value('http://example.com/foo/42')
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
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers(
                    (new Map('string', Header::class))
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
