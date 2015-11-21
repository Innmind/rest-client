<?php

namespace Innmind\Rest\Client\Tests\Cache;

use Innmind\Rest\Client\Cache\InMemoryCache;
use Innmind\Rest\Client\Definition\ResourceDefinition;

class InMemoryCacheTest extends \PHPUnit_Framework_TestCase
{
    protected $c;

    public function setUp()
    {
        $this->c = new InMemoryCache;
    }

    public function testSave()
    {
        $this->assertFalse($this->c->has('foo'));
        $this->assertSame(
            $this->c,
            $this->c->save('foo', $r = new ResourceDefinition('', 'foo', []))
        );
        $this->assertTrue($this->c->has('foo'));
        $this->assertSame(
            $r,
            $this->c->get('foo')
        );
    }

    public function testKeys()
    {
        $this->c->save('bar', new ResourceDefinition('', 'foo', []));

        $this->assertSame(
            ['bar'],
            $this->c->keys()
        );
    }

    public function testRemove()
    {
        $this->c->save('foo', new ResourceDefinition('', 'foo', []));
        $this->assertTrue($this->c->has('foo'));
        $this->assertSame(
            $this->c,
            $this->c->remove('foo')
        );
        $this->assertFalse($this->c->has('foo'));
    }
}
