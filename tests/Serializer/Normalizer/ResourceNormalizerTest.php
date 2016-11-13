<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\{
    Serializer\Normalizer\ResourceNormalizer,
    Serializer\Normalizer\DefinitionNormalizer,
    HttpResource,
    Definition\HttpResource as ResourceDefinition,
    Definition\Access,
    Definition\Types
};
use Innmind\Immutable\Set;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ResourceNormalizerTest extends \PHPUnit_Framework_TestCase
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
        $this->definition = (new DefinitionNormalizer($types))->denormalize(
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
                        'variants' => [],
                        'optional' => true,
                    ],
                    'unwanted' => [
                        'type' => 'int',
                        'access' => ['UPDATE'],
                        'variants' => [],
                        'optional' => true,
                    ]
                ],
                'metas' => [
                    'foo' => ['bar' => 'baz'],
                ],
                'rangeable' => true,
            ],
            ResourceDefinition::class,
            null,
            ['name' => 'foo']
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
                ['resource' => []],
                HttpResource::class
            )
        );
        $this->assertFalse(
            $this->normalizer->supportsDenormalization(
                ['resource' => ''],
                HttpResource::class
            )
        );
        $this->assertFalse(
            $this->normalizer->supportsDenormalization(
                [],
                HttpResource::class
            )
        );
        $this->assertFalse(
            $this->normalizer->supportsDenormalization(
                '',
                HttpResource::class
            )
        );
        $this->assertFalse(
            $this->normalizer->supportsDenormalization(
                ['resource' => []],
                Unknown::class
            )
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingInvalidData()
    {
        $this->normalizer->denormalize(
            [],
            HttpResource::class,
            null,
            [
                'definition' => $this->definition,
                'access' => new Access(
                    (new Set('string'))->add('READ')
                ),
            ]
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingInvalidType()
    {
        $this->normalizer->denormalize(
            ['resource' => []],
            Unknown::class,
            null,
            [
                'definition' => $this->definition,
                'access' => new Access(
                    (new Set('string'))->add('READ')
                ),
            ]
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingWithoutDefinition()
    {
        $this->normalizer->denormalize(
            ['resource' => []],
            Unknown::class,
            null,
            [
                'access' => new Access(
                    (new Set('string'))->add('READ')
                ),
            ]
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingWithInvalidDefinition()
    {
        $this->normalizer->denormalize(
            ['resource' => []],
            Unknown::class,
            null,
            [
                'definition' => [],
                'access' => new Access(
                    (new Set('string'))->add('READ')
                ),
            ]
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingWithoutAccess()
    {
        $this->normalizer->denormalize(
            ['resource' => []],
            Unknown::class,
            null,
            [
                'definition' => $this->definition,
            ]
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingWithInvalidAccess()
    {
        $this->normalizer->denormalize(
            ['resource' => []],
            Unknown::class,
            null,
            [
                'definition' => $this->definition,
                'access' => 'READ',
            ]
        );
    }

    public function testDenormalize()
    {
        $resource = $this->normalizer->denormalize(
            [
                'resource' => [
                    'uuid' => 'some uuid',
                    'url' => 'http://example.com',
                    'unwanted' => 42,
                ],
            ],
            HttpResource::class,
            null,
            [
                'definition' => $this->definition,
                'access' => new Access(
                    (new Set('string'))->add('READ')
                ),
            ]
        );

        $this->assertInstanceOf(HttpResource::class, $resource);
        $this->assertSame('foo', $resource->name());
        $this->assertCount(2, $resource->properties());
        $this->assertSame(
            'some uuid',
            $resource->properties()->get('uuid')->value()
        );
        $this->assertSame(
            'http://example.com',
            $resource->properties()->get('url')->value()
        );
    }

    public function testDenormalizeWithOptionalProperty()
    {
        $resource = $this->normalizer->denormalize(
            [
                'resource' => [
                    'uuid' => 'some uuid',
                ],
            ],
            HttpResource::class,
            null,
            [
                'definition' => $this->definition,
                'access' => new Access(
                    (new Set('string'))->add('READ')
                ),
            ]
        );

        $this->assertInstanceOf(HttpResource::class, $resource);
        $this->assertSame('foo', $resource->name());
        $this->assertCount(1, $resource->properties());
        $this->assertSame(
            'some uuid',
            $resource->properties()->get('uuid')->value()
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\MissingPropertyException
     * @expectedExceptionMessage Missing property "uuid"
     */
    public function testThrowWhenDenormalizingWithMissingProperty()
    {
        $this->normalizer->denormalize(
            [
                'resource' => [],
            ],
            HttpResource::class,
            null,
            [
                'definition' => $this->definition,
                'access' => new Access(
                    (new Set('string'))->add('READ')
                ),
            ]
        );
    }
}
