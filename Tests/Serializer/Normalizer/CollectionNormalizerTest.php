<?php

namespace Innmind\Rest\Client\Tests\Serializer\Normalizer;

use Innmind\Rest\Client\Serializer\Normalizer\CollectionNormalizer;
use Innmind\Rest\Client\Server\Collection;
use Innmind\Rest\Client\Definition\ResourceDefinition;
use Innmind\Rest\Client\Client;

class CollectionNormalizerTest extends \PHPUnit_Framework_TestCase
{
    protected $n;
    protected $client;

    public function setUp()
    {
        $this->n = new CollectionNormalizer;
        $this->client = $this
            ->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testSupportsDenormalization()
    {
        $this->assertTrue($this->n->supportsDenormalization(
            [
                'resources' => [],
                'next' => null,
            ],
            Collection::class
        ));
        $this->assertFalse($this->n->supportsDenormalization(
            [
                'resources' => [],
                'next' => null,
            ],
            'collection'
        ));
        $this->assertFalse($this->n->supportsDenormalization(
            [
                'resources' => [],
                'prev' => null,
            ],
            Collection::class
        ));
        $this->assertFalse($this->n->supportsDenormalization(
            [
                'resources' => '',
                'next' => null,
            ],
            Collection::class
        ));
    }

    /**
     * @expectedException Symfony\Component\Serializer\Exception\LogicException
     * @expectedExceptionMessage You must pass the client and definition in the context
     */
    public function testThrowIfNoClientInContext()
    {
        $this->n->denormalize([], Collection::class, null, [
            'definition' => new ResourceDefinition('', '', []),
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
        $collection = $this->n->denormalize(
            [
                'resources' => [
                    'http://xn--example.com/foo/bar/1',
                ],
                'next' => 'http://xn--example.com/foo/bar/?offset=42',
            ],
            Collection::class,
            null,
            [
                'definition' => $def = new ResourceDefinition('', '', []),
                'client' => $this->client,
            ]
        );

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertSame(1, $collection->count());
        $this->assertSame(
            ['http://xn--example.com/foo/bar/1'],
            $collection->getLinks()
        );
        $this->assertSame($def, $collection->getDefinition());
        $this->assertTrue($collection->hasNextPage());
    }
}
