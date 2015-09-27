<?php

namespace Innmind\Rest\Client\Tests;

use Innmind\Rest\Client\Resource;

class ResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testSet()
    {
        $r = new Resource;

        $this->assertFalse($r->has('foo'));
        $this->assertSame($r, $r->set('foo', 'bar'));
        $this->assertTrue($r->has('foo'));
        $this->assertSame('bar', $r->get('foo'));
    }

    public function testRemove()
    {
        $r = new Resource;

        $r->set('foo', 'bar');
        $this->assertSame($r, $r->remove('foo'));
        $this->assertFalse($r->has('foo'));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Unknown property "foo"
     */
    public function testThrowIfTryingToGetUndefinedProperty()
    {
        $r = new Resource;

        $r->get('foo');
    }
}
