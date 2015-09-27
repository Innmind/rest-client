<?php

namespace Innmind\Rest\Client\Tests\Server;

use Innmind\Rest\Client\Server\Resource;
use Innmind\Rest\Client\Server\Collection;
use Innmind\Rest\Client\Definition\Resource as Definition;
use Innmind\Rest\Client\Definition\Property;
use Innmind\Rest\Client\Client;

class ResourceTest extends \PHPUnit_Framework_TestCase
{
    protected $r;
    protected $d;

    public function setUp()
    {
        $this->r = new Resource(
            $this->d = new Definition('', '', [
                'foobar' => (new Property('foobar', 'resource', [], ['baz']))
                    ->linkTo(
                        $this
                            ->getMockBuilder(Definition::class)
                            ->disableOriginalConstructor()
                            ->getMock()
                    ),
            ]),
            ['foo' => 'bar'],
            [
                'bar' => 'http://xn--example.com/foo/bar/1',
                'foobar' => ['http://xn--example.com/foo/bar/2'],
            ],
            $client = $this
                ->getMockBuilder(Client::class)
                ->disableOriginalConstructor()
                ->getMock()
        );

        $client
            ->method('read')
            ->willReturn(
                $this
                    ->getMockBuilder(Resource::class)
                    ->disableOriginalConstructor()
                    ->getMock()
            );
    }

    public function testGetDefinition()
    {
        $this->assertSame(
            $this->d,
            $this->r->getDefinition()
        );
    }

    public function testHas()
    {
        $this->assertTrue($this->r->has('foo'));
        $this->assertTrue($this->r->has('bar'));
        $this->assertTrue($this->r->has('foobar'));
        $this->assertFalse($this->r->has('baz'));
    }

    public function testGet()
    {
        $this->assertSame('bar', $this->r->get('foo'));
        $bar = $this->r->get('bar');
        $this->assertInstanceOf(Resource::class, $bar);
        $this->assertSame($bar, $this->r->get('bar'));
        $foobar = $this->r->get('foobar');
        $this->assertInstanceOf(Collection::class, $foobar);
        $this->assertSame(1, $foobar->count());
        $this->assertSame($foobar, $this->r->get('foobar'));
        $this->assertInstanceOf(Definition::class, $foobar->getDefinition());
        $this->assertSame($foobar, $this->r->get('baz'));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Property "foobaz" not found
     */
    public function testThrowIfTryingToAccessUnknownProperty()
    {
        $this->r->get('foobaz');
    }
}
