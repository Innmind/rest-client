<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client;

use Innmind\Rest\Client\{
    Formats,
    Format\Format,
    Format\MediaType,
};
use Innmind\Immutable\{
    Map,
    SetInterface,
    Set,
};
use PHPUnit\Framework\TestCase;

class FormatsTest extends TestCase
{
    public function testInterface()
    {
        $formats = new Formats(
            $all = Map::of('string', Format::class)
                (
                    'json',
                    $format = new Format(
                        'json',
                        Set::of(
                            MediaType::class,
                            new MediaType('application/json', 42)
                        ),
                        42
                    )
                )
        );

        $this->assertSame($all, $formats->all());
        $this->assertSame($format, $formats->get('json'));
    }

    public function testOf()
    {
        $formats = Formats::of(
            $format = new Format(
                'json',
                Set::of(
                    MediaType::class,
                    new MediaType('application/json', 42)
                ),
                42
            )
        );

        $this->assertInstanceOf(Formats::class, $formats);
        $this->assertSame($format, $formats->get('json'));
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 1 must be of type MapInterface<string, Innmind\Rest\Client\Format\Format>
     */
    public function testThrowWhenInvalidMapKey()
    {
        new Formats(new Map('int', Format::class));
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 1 must be of type MapInterface<string, Innmind\Rest\Client\Format\Format>
     */
    public function testThrowWhenInvalidMapValue()
    {
        new Formats(new Map('string', 'string'));
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\DomainException
     */
    public function testThrowWhenEmptyMap()
    {
        new Formats(new Map('string', Format::class));
    }

    public function testMediaTypes()
    {
        $formats = new Formats(
            Map::of('string', Format::class)
                (
                    'json',
                    new Format(
                        'json',
                        Set::of(
                            MediaType::class,
                            $json = new MediaType('application/json', 42)
                        ),
                        42
                    )
                )
                (
                    'html',
                    new Format(
                        'html',
                        Set::of(
                            MediaType::class,
                            $html = new MediaType('text/html', 40),
                            $xhtml = new MediaType('text/xhtml', 0)
                        ),
                        0
                    )
                )
        );

        $types = $formats->mediaTypes();
        $this->assertInstanceOf(SetInterface::class, $types);
        $this->assertSame(MediaType::class, (string) $types->type());
        $this->assertSame(3, $types->size());
        $this->assertTrue($types->contains($json));
        $this->assertTrue($types->contains($html));
        $this->assertTrue($types->contains($xhtml));
    }

    public function testFromMediaType()
    {
        $formats = new Formats(
            Map::of('string', Format::class)
                (
                    'json',
                    $json = new Format(
                        'json',
                        Set::of(
                            MediaType::class,
                            new MediaType('application/json', 42)
                        ),
                        42
                    )
                )
                (
                    'html',
                    $html = new Format(
                        'html',
                        Set::of(
                            MediaType::class,
                            new MediaType('text/html', 40),
                            new MediaType('text/xhtml', 0)
                        ),
                        0
                    )
                )
        );

        $this->assertSame($json, $formats->fromMediaType('application/json'));
        $this->assertSame($html, $formats->fromMediaType('text/html'));
        $this->assertSame($html, $formats->fromMediaType('text/xhtml'));
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenNoFormatForWishedMediaType()
    {
        $formats = new Formats(
            Map::of('string', Format::class)
                (
                    'html',
                    new Format(
                        'html',
                        Set::of(
                            MediaType::class,
                            new MediaType('text/html', 40),
                            new MediaType('text/xhtml', 0)
                        ),
                        0
                    )
                )
        );

        $formats->fromMediaType('application/json');
    }

    public function testMatching()
    {
        $formats = new Formats(
            Map::of('string', Format::class)
                (
                    'json',
                    new Format(
                        'json',
                        Set::of(
                            MediaType::class,
                            new MediaType('application/json', 42)
                        ),
                        42
                    )
                )
                (
                    'html',
                    $html = new Format(
                        'html',
                        Set::of(
                            MediaType::class,
                            new MediaType('text/html', 40),
                            new MediaType('text/xhtml', 0)
                        ),
                        0
                    )
                )
        );

        $format = $formats->matching('text/html, application/json;q=0.5, *;q=0.1');

        $this->assertSame($html, $format);
    }

    public function testMatchingWhenAcceptingEverything()
    {
        $formats = new Formats(
            Map::of('string', Format::class)
                (
                    'json',
                    $json = new Format(
                        'json',
                        Set::of(
                            MediaType::class,
                            new MediaType('application/json', 42)
                        ),
                        42
                    )
                )
                (
                    'html',
                    new Format(
                        'html',
                        Set::of(
                            MediaType::class,
                            new MediaType('text/html', 40),
                            new MediaType('text/xhtml', 0)
                        ),
                        0
                    )
                )
        );

        $format = $formats->matching('*');

        $this->assertSame($json, $format);
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenCantMatch()
    {
        $formats = new Formats(
            Map::of('string', Format::class)
                (
                    'json',
                    new Format(
                        'json',
                        Set::of(
                            MediaType::class,
                            new MediaType('application/json', 42)
                        ),
                        42
                    )
                )
                (
                    'html',
                    new Format(
                        'html',
                        Set::of(
                            MediaType::class,
                            new MediaType('text/html', 40),
                            new MediaType('text/xhtml', 0)
                        ),
                        0
                    )
                )
        );

        $formats->matching('text/plain');
    }
}
