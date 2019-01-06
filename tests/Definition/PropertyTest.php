<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\Definition\{
    Property,
    Type,
    Access,
};
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class PropertyTest extends TestCase
{
    /**
     * @expectedException Innmind\Rest\Client\Exception\DomainException
     */
    public function testThrowWhenEmptyName()
    {
        new Property(
            '',
            $this->createMock(Type::class),
            new Access,
            new Set('string'),
            true
        );
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 4 must be of type SetInterface<string>
     */
    public function testThrowWhenInvalidVariants()
    {
        new Property(
            'foo',
            $this->createMock(Type::class),
            new Access,
            new Set('int'),
            true
        );
    }

    public function testInterface()
    {
        $property = new Property(
            'foo',
            $type = $this->createMock(Type::class),
            $access = new Access,
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
            $this->createMock(Type::class),
            new Access,
            new Set('string'),
            false
        );

        $this->assertFalse($property->isOptional());
    }
}
