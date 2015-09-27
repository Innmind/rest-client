<?php

namespace Innmind\Rest\Client\Tests\Server;

use Innmind\Rest\Client\Server\Collection;
use Innmind\Rest\Client\Server\CollectionInterface;
use Innmind\Rest\Client\Definition\Resource as Definition;
use Innmind\Rest\Client\Client;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    protected $c;
    protected $d;

    public function setUp()
    {
        $this->c = new Collection(
            $this->d = new Definition('', '', []),
            ['http://xn--example.com/foo/bar/1'],
            'http://xn--example.com/foo/bar/?offset=42',
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

    public function testInterfaces()
    {
        $this->assertInstanceOf(CollectionInterface::class, $this->c);
        $this->assertInstanceOf(\Iterator::class, $this->c);
        $this->assertInstanceOf(\Countable::class, $this->c);
    }

    public function testGetDefinition()
    {
        $this->assertSame(
            $this->d,
            $this->c->getDefinition()
        );
    }

    public function testNextPage()
    {
        $this->assertTrue($this->c->hasNextPage());
        $this->assertSame(
            'http://xn--example.com/foo/bar/?offset=42',
            $this->c->getNextPage()
        );
    }

    public function testGetLinks()
    {
        $this->assertSame(
            ['http://xn--example.com/foo/bar/1'],
            $this->c->getLinks()
        );
    }

    public function testCount()
    {
        $this->assertSame(1, $this->c->count());
    }

    public function testIterator()
    {
        $refl = new \ReflectionObject($this->c);
        $refl = $refl->getProperty('next');
        $refl->setAccessible(true);
        $refl->setValue($this->c, null);

        $this->assertTrue($this->c->valid());
        $this->assertSame(0, $this->c->key());
        $current = $this->c->current();
        $this->assertInstanceOf(Resource::class, $current);
        $this->assertSame(null, $this->c->next());
        $this->assertSame(1, $this->c->key());
        $this->assertFalse($this->c->valid());
        $this->assertSame(null, $this->c->rewind());
        $this->assertSame(0, $this->c->key());
        $this->assertSame($current, $this->c->current());
    }
}
