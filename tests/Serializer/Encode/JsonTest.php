<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Serializer\Encode;

use Innmind\Rest\Client\Serializer\{
    Encode\Json,
    Encode,
};
use Innmind\Stream\Readable;
use PHPUnit\Framework\TestCase;

class JsonTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Encode::class, new Json);
    }

    public function testInvokation()
    {
        $stream = (new Json)(['foo' => 'bar']);

        $this->assertInstanceOf(Readable::class, $stream);
        $this->assertSame(
            '{"foo":"bar"}',
            $stream->toString(),
        );
    }
}
