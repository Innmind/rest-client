<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\HttpResource;

use Innmind\Rest\Client\HttpResource\Property;

class PropertyTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $property = new Property('foo', ['bar']);

        $this->assertSame('foo', $property->name());
        $this->assertSame(['bar'], $property->value());
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenEmptyName()
    {
        new Property('', ['bar']);
    }
}
