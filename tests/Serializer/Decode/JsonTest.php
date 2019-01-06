<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Serializer\Decode;

use Innmind\Rest\Client\Serializer\{
    Decode\Json,
    Decode,
};
use Innmind\Filesystem\Stream\StringStream;
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
            (new Json)(new StringStream('{"foo":"bar"}'))
        );
    }
}
