<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Format;

use Innmind\Rest\Client\Format\MediaType;
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

    /**
     * @expectedException Innmind\Filesystem\Exception\InvalidMediaTypeStringException
     */
    public function testThrowWhenInvalidMediaTypeGiven()
    {
        new MediaType('foo', 42);
    }
}
