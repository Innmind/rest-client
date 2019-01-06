<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Link\Parameter;

use Innmind\Rest\Client\Link\{
    Parameter\Parameter,
    Parameter as ParameterInterface,
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

    /**
     * @expectedException Innmind\Rest\Client\Exception\DomainException
     */
    public function testThrowWhenEmptyKey()
    {
        new Parameter('', 'bar');
    }
}
