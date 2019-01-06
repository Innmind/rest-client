<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Format;

use Innmind\Rest\Client\Format\{
    Format,
    MediaType,
};
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class FormatTest extends TestCase
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
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 2 must be of type SetInterface<Innmind\Rest\Client\Format\MediaType>
     */
    public function testThrowWhenInvalidMediaType()
    {
        new Format('foo', new Set('string'), 42);
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\DomainException
     */
    public function testThrowWhenNoMediaType()
    {
        new Format('foo', new Set(MediaType::class), 42);
    }
}
