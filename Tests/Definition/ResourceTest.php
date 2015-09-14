<?php

namespace Innmind\Rest\Client\Tests\Definition;

use Innmind\Rest\Client\Definition\Resource;
use Innmind\Rest\Client\Definition\Property;

class ResourceTest extends \PHPUnit_Framework_TestCase
{
    protected $p;

    public function setUp()
    {
        $this->p = $this
            ->getMockBuilder(Property::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testBuild()
    {
        $r = new Resource(
            '',
            'foo',
            [$this->p],
            [],
            true
        );

        $this->assertSame(
            'foo',
            $r->getId()
        );
        $this->assertSame(
            [$this->p],
            $r->getProperties()
        );
        $this->assertSame(
            [],
            $r->getMetas()
        );
        $this->assertTrue($r->isFresh());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage A resource property must be a Property object (string given)
     */
    public function testThrowIfInvalidException()
    {
        new Resource('', 'foo', ['foo']);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Unknown property foo
     */
    public function testThrowIfUnknownProperty()
    {
        $r = new Resource(
            '',
            'foo',
            [
                'bar' => $this->p,
            ]
        );

        $this->assertTrue($r->hasProperty('bar'));
        $this->assertFalse($r->hasProperty('foo'));

        $this->assertSame(
            $this->p,
            $r->getProperty('bar')
        );
        $r->getProperty('foo');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Unknown meta foo
     */
    public function testThrowIfUnknownMeta()
    {
        $r = new Resource('', 'foo', [], ['bar' => 'baz']);

        $this->assertTrue($r->hasMeta('bar'));
        $this->assertFalse($r->hasMeta('foo'));

        $this->assertSame(
            'baz',
            $r->getMeta('bar')
        );
        $r->getMeta('foo');
    }

    public function testRefresh()
    {
        $r = new Resource('', 'foo', []);

        $this->assertFalse($r->isFresh());
        $r->refresh('bar', ['foo' => $this->p], ['foo' => 'bar']);
        $this->assertTrue($r->isFresh());
        $this->assertSame(
            'bar',
            $r->getId()
        );
        $this->assertSame(
            ['foo' => $this->p],
            $r->getProperties()
        );
        $this->assertSame(
            ['foo' => 'bar'],
            $r->getMetas()
        );
    }

    public function testBelongsTo()
    {
        $r = new Resource('http://example.com/foo/bar/', 'uuid', []);

        $this->assertTrue($r->belongsTo('http://example.com/foo/bar/'));
        $this->assertTrue($r->belongsTo('http://example.com/foo/bar/?offset=42&limit=42'));
        $this->assertTrue($r->belongsTo('http://example.com/foo/bar/some-id'));
        $this->assertFalse($r->belongsTo('http://example.com/foo/bar/subdir/some-id'));
    }
}
