<?php

namespace Innmind\Rest\Client\Tests\Definition;

use Innmind\Rest\Client\Definition\Property;
use Innmind\Rest\Client\Definition\ResourceDefinition;

class PropertyTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $p = new Property(
            'foo',
            'int',
            ['READ'],
            ['bar'],
            true
        );

        $this->assertSame(
            'foo',
            $p->getName()
        );
        $this->assertSame(
            'foo',
            (string) $p
        );
        $this->assertSame(
            'int',
            $p->getType()
        );
        $this->assertSame(
            ['READ'],
            $p->getAccess()
        );
        $this->assertTrue($p->hasAccess('READ'));
        $this->assertFalse($p->hasAccess('CREATE'));
        $this->assertSame(
            ['bar'],
            $p->getVariants()
        );
        $this->assertTrue($p->isVariantOf('bar'));
        $this->assertTrue($p->isVariantOf('foo'));
        $this->assertFalse($p->isVariantOf('baz'));
        $this->assertTrue($p->isOptional());
    }

    public function testLinkTo()
    {
        $p = new Property('foo', 'resource', [], []);
        $this->assertSame(
            $p,
            $p->linkTo($r = new ResourceDefinition('', 'foo', []))
        );
        $this->assertSame(
            $r,
            $p->getResource()
        );
        $p = new Property('foo', 'array', [], [], false, 'resource');
        $this->assertSame(
            $p,
            $p->linkTo($r = new ResourceDefinition('', 'foo', []))
        );
        $this->assertSame(
            $r,
            $p->getResource()
        );
    }

    /**
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage A property can be linked to a resource only if it is of type "resource"
     */
    public function testThrowIfTryingToAssociateAResourceOnWrongProperty()
    {
        $p = new Property('foo', 'int', [], []);
        $p->linkTo(new ResourceDefinition('', 'foo', []));
    }

    /**
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage The property foo is not linked to a resource
     */
    public function testThrowWhenTryingToAccessResourceWhenNotAvailable()
    {
        $p = new Property('foo', 'int', [], []);
        $p->getResource();
    }
}
