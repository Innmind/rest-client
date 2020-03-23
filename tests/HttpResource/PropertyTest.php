<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\HttpResource;

use Innmind\Rest\Client\{
    HttpResource\Property,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class PropertyTest extends TestCase
{
    public function testInterface()
    {
        $property = new Property('foo', ['bar']);

        $this->assertSame('foo', $property->name());
        $this->assertSame(['bar'], $property->value());
    }

    public function testThrowWhenEmptyName()
    {
        $this->expectException(DomainException::class);

        new Property('', ['bar']);
    }
}
