<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Serializer\Decode;

use Innmind\Rest\Client\{
    Serializer\Decode\Json,
    Serializer\Decode,
    Exception\LogicException,
};
use Innmind\Stream\Readable\Stream;
use PHPUnit\Framework\TestCase;

class JsonTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Decode::class, new Json);
    }

    public function testInvokation()
    {
        $this->assertSame(
            ['foo' => 'bar'],
            (new Json)('json', Stream::ofContent('{"foo":"bar"}'))
        );
    }

    public function testThrowWhenInvalidFormat()
    {
        $this->expectException(LogicException::class);

        (new Json)('xml', Stream::ofContent('{"foo":"bar"}'));
    }
}
