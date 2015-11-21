<?php

namespace Innmind\Rest\Client\Tests\Cache;

use Innmind\Rest\Client\Cache\FileCache;
use Innmind\Rest\Client\Definition\ResourceDefinition;
use Innmind\Rest\Client\Definition\Property;

class FileCacheTest extends \PHPUnit_Framework_TestCase
{
    public function testDumpDefinitions()
    {
        $path = sys_get_temp_dir().'/resources.php';
        if (file_exists($path)) {
            unlink($path);
        }
        $c = new FileCache($path);
        $this->assertSame(
            [],
            $c->keys()
        );
        $link = new ResourceDefinition('http://example.com/foo/bar/', 'uuid', [], []);
        $c->save(
            'foo',
            new ResourceDefinition(
                'http://example.com/foo/baz/',
                'uuid',
                [
                    'bar' => new Property(
                        'bar',
                        'array',
                        ['READ', 'UPDATE'],
                        ['baz'],
                        true,
                        'string'
                    ),
                ],
                [
                    'description' => 'some " weird \'stuff',
                ],
                true
            )
        );
        $c->save(
            'baz',
            new ResourceDefinition(
                'http://example.com/foobar/',
                'uuid',
                [
                    'baz' => (new Property(
                        'baz',
                        'array',
                        ['READ'],
                        [],
                        false,
                        'resource'
                    ))->linkTo($link)
                ]
            )
        );
        $c->save('bar', $link);
        unset($c);
        $c = new FileCache($path);
        $this->assertSame(
            ['foo', 'baz', 'bar'],
            $c->keys()
        );
        $r = $c->get('foo');
        $this->assertSame(
            'uuid',
            $r->getId()
        );
        $this->assertSame(
            'http://example.com/foo/baz/',
            $r->getUrl()
        );
        $this->assertSame(
            ['description' => 'some " weird \'stuff'],
            $r->getMetas()
        );
        $this->assertTrue($r->hasProperty('bar'));
        $p = $r->getProperty('bar');
        $this->assertSame(
            'bar',
            $p->getName()
        );
        $this->assertSame(
            'array',
            $p->getType()
        );
        $this->assertSame(
            ['READ', 'UPDATE'],
            $p->getAccess()
        );
        $this->assertSame(
            ['baz'],
            $p->getVariants()
        );
        $this->assertTrue($p->isOptional());
        $this->assertSame(
            'string',
            $p->getInnerType()
        );
        $this->assertSame(
            $c->get('bar'),
            $c->get('baz')->getProperty('baz')->getResource()
        );
        $this->assertFalse($c->get('baz')->getProperty('baz')->isOptional());
    }
}
