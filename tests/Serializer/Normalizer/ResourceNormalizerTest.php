<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\{
    Serializer\Normalizer\ResourceNormalizer,
    Serializer\Denormalizer\DenormalizeDefinition,
    HttpResource,
    HttpResource\Property,
    Definition\HttpResource as ResourceDefinition,
    Definition\Access,
    Definition\Types,
};
use Innmind\Immutable\{
    Set,
    Map,
};
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use PHPUnit\Framework\TestCase;

class ResourceNormalizerTest extends TestCase
{
    private $normalizer;
    private $definition;

    public function setUp()
    {
        $this->normalizer = new ResourceNormalizer;
        $types = new Types;
        Types::defaults()->foreach(function(string $class) use ($types) {
            $types->register($class);
        });
        $this->definition = (new DenormalizeDefinition($types))(
            [
                'url' => 'http://example.com/foo',
                'identity' => 'uuid',
                'properties' => [
                    'uuid' => [
                        'type' => 'string',
                        'access' => ['READ'],
                        'variants' => ['guid'],
                        'optional' => false,
                    ],
                    'url' => [
                        'type' => 'string',
                        'access' => ['READ', 'CREATE', 'UPDATE'],
                        'variants' => ['uri'],
                        'optional' => true,
                    ],
                    'unwanted' => [
                        'type' => 'int',
                        'access' => ['UPDATE'],
                        'variants' => [],
                        'optional' => true,
                    ],
                    'onCreate' => [
                        'type' => 'int',
                        'access' => ['CREATE'],
                        'variants' => [],
                        'optional' => false,
                    ],
                ],
                'metas' => [
                    'foo' => ['bar' => 'baz'],
                ],
                'linkable_to' => [],
                'rangeable' => true,
            ],
            'foo'
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            NormalizerInterface::class,
            $this->normalizer
        );
    }

    public function testSupportsNormalization()
    {
        $this->assertTrue(
            $this->normalizer->supportsNormalization(
                HttpResource::of('foo')
            )
        );
        $this->assertFalse(
            $this->normalizer->supportsNormalization(
                new \stdClass
            )
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenNormalizingInvalidData()
    {
        $this->normalizer->normalize(
            new \stdClass,
            null,
            [
                'definition' => $this->definition,
                'access' => new Access('CREATE'),
            ]
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenNormalizingWithoutDefinition()
    {
        $this->normalizer->normalize(
            HttpResource::of('foo'),
            null,
            [
                'access' => new Access('CREATE'),
            ]
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenNormalizingWithInvalidDefinition()
    {
        $this->normalizer->normalize(
            HttpResource::of('foo'),
            null,
            [
                'definition' => [],
                'access' => new Access('CREATE'),
            ]
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenNormalizingWithoutAccess()
    {
        $this->normalizer->normalize(
            HttpResource::of('foo'),
            null,
            [
                'definition' => $this->definition,
            ]
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenNormalizingWithInvalidAccess()
    {
        $this->normalizer->normalize(
            HttpResource::of('foo'),
            null,
            [
                'definition' => $this->definition,
                'access' => ['CREATE'],
            ]
        );
    }

    public function testNormalize()
    {
        $resource = $this->normalizer->normalize(
            HttpResource::of(
                'foo',
                new Property('url', 'http://example.com/'),
                new Property('onCreate', '42')
            ),
            null,
            [
                'definition' => $this->definition,
                'access' => new Access('CREATE'),
            ]
        );

        $this->assertSame(
            [
                'resource' => [
                    'url' => 'http://example.com/',
                    'onCreate' => 42,
                ],
            ],
            $resource
        );
    }

    public function testNormalizeWithVariant()
    {
        $resource = $this->normalizer->normalize(
            HttpResource::of(
                'foo',
                new Property('uri', 'http://example.com/'),
                new Property('onCreate', '42')
            ),
            null,
            [
                'definition' => $this->definition,
                'access' => new Access('CREATE'),
            ]
        );

        $this->assertSame(
            [
                'resource' => [
                    'url' => 'http://example.com/',
                    'onCreate' => 42,
                ],
            ],
            $resource
        );
    }

    public function testNormalizeWithOptionalProperty()
    {
        $resource = $this->normalizer->normalize(
            HttpResource::of(
                'foo',
                new Property('onCreate', '42')
            ),
            null,
            [
                'definition' => $this->definition,
                'access' => new Access('CREATE'),
            ]
        );

        $this->assertSame(
            ['resource' => ['onCreate' => 42]],
            $resource
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\MissingProperty
     * @expectedExceptionMessage Missing property "onCreate"
     */
    public function testThrowWhenNormalizingWithMissingProperty()
    {
        $this->normalizer->normalize(
            HttpResource::of('foo'),
            null,
            [
                'definition' => $this->definition,
                'access' => new Access('CREATE'),
            ]
        );
    }
}
