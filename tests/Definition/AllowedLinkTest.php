<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\{
    Definition\AllowedLink,
    Link,
    Link\Parameter\Parameter,
    Identity,
};
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class AllowedLinkTest extends TestCase
{
    public function testInterface()
    {
        $allowed = new AllowedLink('path', 'rel', $parameters = Set::of('string'));

        $this->assertSame('path', $allowed->resourcePath());
        $this->assertSame('rel', $allowed->relationship());
        $this->assertSame($parameters, $allowed->parameters());
    }

    public function testThrowWhenInvalidParameterSet()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 3 must be of type Set<string>');

        new AllowedLink('path', 'rel', Set::of('object'));
    }

    public function testAllows()
    {
        $allowed = new AllowedLink('path', 'rel', Set::of('string', 'foo'));

        $this->assertTrue($allowed->allows(Link::of(
            'path',
            $this->createMock(Identity::class),
            'rel',
            new Parameter('foo', 'bar')
        )));
    }

    public function testDoesntAllowWhenDifferentDefinition()
    {
        $allowed = new AllowedLink('other_path', 'rel', Set::of('string', 'foo'));

        $this->assertFalse($allowed->allows(Link::of(
            'path',
            $this->createMock(Identity::class),
            'rel',
            new Parameter('foo', 'bar')
        )));
    }

    public function testDoesntAllowWhenDifferentRelationship()
    {
        $allowed = new AllowedLink('path', 'other_rel', Set::of('string', 'foo'));

        $this->assertFalse($allowed->allows(Link::of(
            'path',
            $this->createMock(Identity::class),
            'rel',
            new Parameter('foo', 'bar')
        )));
    }

    public function testDoesntAllowWhenExpectedParameterNotFound()
    {
        $allowed = new AllowedLink('path', 'rel', Set::of('string', 'foo'));

        $this->assertFalse($allowed->allows(Link::of(
            'path',
            $this->createMock(Identity::class),
            'rel'
        )));
    }
}
