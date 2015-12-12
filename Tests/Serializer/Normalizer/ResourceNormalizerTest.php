<?php

namespace Innmind\Rest\Client\Tests\Serializer\Normalizer;

use Innmind\Rest\Client\Serializer\Normalizer\ResourceNormalizer;
use Innmind\Rest\Client\Server\HttpResource as ServerResource;
use Innmind\Rest\Client\Server\HttpResourceInterface as ServerResourceInterface;
use Innmind\Rest\Client\Definition\ResourceDefinition as Definition;
use Innmind\Rest\Client\Definition\Property;
use Innmind\Rest\Client\Client;
use Innmind\Rest\Client\HttpResource;

class ResourceNormalizerTest extends \PHPUnit_Framework_TestCase
{
    protected $n;
    protected $client;

    public function setUp()
    {
        $this->n = new ResourceNormalizer;
        $this->client = $this
            ->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testSupportsDenormalization()
    {
        $this->assertTrue($this->n->supportsDenormalization(
            [
                'resource' => [
                    'properties' => [],
                    'subResources' => [],
                ],
            ],
            ServerResourceInterface::class
        ));
        $this->assertFalse($this->n->supportsDenormalization(
            [
                'resource' => [
                    'properties' => [],
                ],
            ],
            ServerResourceInterface::class
        ));
        $this->assertFalse($this->n->supportsDenormalization(
            [
                'resource' => [
                    'subResources' => [],
                ],
            ],
            ServerResourceInterface::class
        ));
        $this->assertFalse($this->n->supportsDenormalization(
            [
                'resource' => [
                    'properties' => '',
                    'subResources' => [],
                ],
            ],
            ServerResourceInterface::class
        ));
        $this->assertFalse($this->n->supportsDenormalization(
            [
                'resource' => [
                    'properties' => [],
                    'subResources' => '',
                ],
            ],
            ServerResourceInterface::class
        ));
        $this->assertFalse($this->n->supportsDenormalization(
            [
                'resource' => [
                    'properties' => [],
                    'subResources' => [],
                ],
            ],
            'resource'
        ));
    }

    /**
     * @expectedException Symfony\Component\Serializer\Exception\LogicException
     * @expectedExceptionMessage You must pass the client and definition in the context
     */
    public function testThrowIfNoClientInContext()
    {
        $this->n->denormalize([], Collection::class, null, [
            'definition' => new Definition('', '', []),
        ]);
    }

    /**
     * @expectedException Symfony\Component\Serializer\Exception\LogicException
     * @expectedExceptionMessage You must pass the client and definition in the context
     */
    public function testThrowIfNoDefinitionInContext()
    {
        $this->n->denormalize([], Collection::class, null, [
            'client' => $this->client,
        ]);
    }

    public function testDenormalize()
    {
        $resource = $this->n->denormalize(
            [
                'resource' => [
                    'properties' => [
                        'foo' => 'bar',
                    ],
                    'subResources' => [
                        'bar' => [
                            'foo',
                        ],
                    ],
                ],
            ],
            ServerResourceInterface::class,
            null,
            [
                'definition' => $def = new Definition('', '', []),
                'client' => $this->client,
            ]
        );

        $this->assertInstanceOf(ServerResource::class, $resource);
        $this->assertSame($def, $resource->getDefinition());
        $this->assertTrue($resource->has('foo'));
        $this->assertTrue($resource->has('bar'));
    }

    public function testSupportsNormalization()
    {
        $this->assertTrue($this->n->supportsNormalization(new HttpResource));
        $this->assertFalse($this->n->supportsNormalization(['resource' => []]));
    }

    public function testNormalization()
    {
        $sub = new HttpResource;
        $sub->set('foobar', 'bar');
        $r = new HttpResource;
        $r->set('foo', 'bar');
        $r->set('bar', $sub);
        $r->set('baz', 'foo');
        $r->set('collz', [
            [
                'foobar' => 'bar',
            ],
            $sub
        ]);
        $r->set('date', $date = new \DateTime);
        $subDef = new Definition(
            '',
            'id',
            [
                'foobar' => new Property('foobar', 'string', ['CREATE'], []),
            ]
        );
        $def = new Definition(
            '',
            'id',
            [
                'foo' => new Property('foo', 'string', ['CREATE'], []),
                'bar' => (new Property('bar', 'resource', ['CREATE'], []))
                    ->linkTo($subDef),
                'baz' => new Property('baz', 'string', ['READ'], []),
                'coll' => (new Property('coll', 'array', ['CREATE'], ['collz'], false, 'resource'))
                    ->linkTo($subDef),
                'date' => new Property('date', 'date', ['CREATE'], []),
            ]
        );

        $expected = [
            'resource' => [
                'foo' => 'bar',
                'bar' => [
                    'foobar' => 'bar',
                ],
                'coll' => [
                    ['foobar' => 'bar'],
                    ['foobar' => 'bar'],
                ],
                'date' => $date->format(\DateTime::ISO8601)
            ],
        ];

        $this->assertSame(
            $expected,
            $this->n->normalize($r, null, [
                'definition' => $def,
                'action' => 'CREATE',
            ])
        );
    }
}
