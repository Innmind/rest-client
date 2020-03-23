<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Request;

use Innmind\Rest\Client\{
    Request\Range,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class RangeTest extends TestCase
{
    public function testInterface()
    {
        $range = new Range(10, 20);

        $this->assertSame(10, $range->firstPosition());
        $this->assertSame(20, $range->lastPosition());
    }

    public function testThrowWhenFirstPositionNegative()
    {
        $this->expectException(DomainException::class);

        new Range(-1, 20);
    }

    public function testThrowWhenLastPositionLowerThanFirstOne()
    {
        $this->expectException(DomainException::class);

        new Range(10, 5);
    }
}
