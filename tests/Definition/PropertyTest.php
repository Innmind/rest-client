<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\Definition\{
    Property,
    TypeInterface,
    Access
};
use Innmind\Immutable\Set;

class PropertyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenEmptyName()
    {
        new Property(
            '',
            $this->createMock(TypeInterface::class),
            new Access(new Set('string')),
            new Set('string'),
            true
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidVariants()
    {
        new Property(
            'foo',
            $this->createMock(TypeInterface::class),
            new Access(new Set('string')),
            new Set('int'),
            true
        );
    }

    public function testInterface()
    {
        $property = new Property(
            'foo',
            $type = $this->createMock(TypeInterface::class),
            $access = new Access(new Set('string')),
            $variants = new Set('string'),
            true
        );

        $this->assertSame('foo', $property->name());
        $this->assertSame($type, $property->type());
        $this->assertSame($access, $property->access());
        $this->assertSame($variants, $property->variants());
        $this->assertTrue($property->isOptional());
    }

    public function assertNotOptional()
    {
        $property = new Property(
            'foo',
            $this->createMock(TypeInterface::class),
            new Access(new Set('string')),
            new Set('string'),
            false
        );

        $this->assertFalse($property->isOptional());
    }
}