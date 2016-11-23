<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Format;

use Innmind\Rest\Client\Format\{
    Format,
    MediaType
};
use Innmind\Immutable\Set;

class FormatTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $format = new Format(
            'json',
            $types = (new Set(MediaType::class))
                ->add(new MediaType('application/json', 42)),
            24
        );

        $this->assertSame('json', $format->name());
        $this->assertSame('json', (string) $format);
        $this->assertSame($types, $format->mediaTypes());
        $this->assertSame(24, $format->priority());
    }

    public function testPreferredMediaType()
    {
        $format = new Format(
            'json',
            (new Set(MediaType::class))
                ->add(new MediaType('application/json', 42))
                ->add(new MediaType('text/json', 0)),
            24
        );

        $mime = $format->preferredMediaType();
        $this->assertInstanceOf(MediaType::class, $mime);
        $this->assertSame('application/json', (string) $mime);
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidMediaType()
    {
        new Format('foo', new Set('string'), 42);
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenNoMediaType()
    {
        new Format('foo', new Set(MediaType::class), 42);
    }
}
