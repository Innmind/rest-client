<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Format;

use Innmind\Rest\Client\Format\MediaType;
use Innmind\MediaType\Exception\InvalidMediaTypeString;
use PHPUnit\Framework\TestCase;

class MediaTypeTest extends TestCase
{
    public function testInterface()
    {
        $mime = new MediaType($string = 'application/vnd.media-type+suffix', 42);

        $this->assertSame($string, (string) $mime);
        $this->assertSame('application', $mime->topLevel());
        $this->assertSame('vnd.media-type', $mime->subType());
        $this->assertSame('suffix', $mime->suffix());
        $this->assertSame(42, $mime->priority());
    }

    public function testThrowWhenInvalidMediaTypeGiven()
    {
        $this->expectException(InvalidMediaTypeString::class);

        new MediaType('foo', 42);
    }
}
