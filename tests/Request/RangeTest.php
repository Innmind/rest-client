<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Request;

use Innmind\Rest\Client\Request\Range;
use PHPUnit\Framework\TestCase;

class RangeTest extends TestCase
{
    public function testInterface()
    {
        $range = new Range(10, 20);

        $this->assertSame(10, $range->firstPosition());
        $this->assertSame(20, $range->lastPosition());
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenFirstPositionNegative()
    {
        new Range(-1, 20);
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenLastPositionLowerThanFirstOne()
    {
        new Range(10, 5);
    }
}
