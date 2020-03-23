<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Link\Parameter;

use Innmind\Rest\Client\{
    Link\Parameter\Parameter,
    Link\Parameter as ParameterInterface,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class ParameterTest extends TestCase
{
    public function testInterface()
    {
        $parameter = new Parameter('foo', 'bar');

        $this->assertInstanceOf(ParameterInterface::class, $parameter);
        $this->assertSame('foo', $parameter->key());
        $this->assertSame('bar', $parameter->value());
    }

    public function testThrowWhenEmptyKey()
    {
        $this->expectException(DomainException::class);

        new Parameter('', 'bar');
    }
}
