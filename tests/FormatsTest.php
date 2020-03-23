<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client;

use Innmind\Rest\Client\{
    Formats,
    Format\Format,
    Format\MediaType,
    Exception\InvalidArgumentException,
    Exception\DomainException,
};
use Innmind\Immutable\{
    Map,
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

    public function testThrowWhenInvalidMapKey()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type Map<string, Innmind\Rest\Client\Format\Format>');

        new Formats(Map::of('int', Format::class));
    }

    public function testThrowWhenInvalidMapValue()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type Map<string, Innmind\Rest\Client\Format\Format>');

        new Formats(Map::of('string', 'string'));
    }

    public function testThrowWhenEmptyMap()
    {
        $this->expectException(DomainException::class);

        new Formats(Map::of('string', Format::class));
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
        $this->assertInstanceOf(Set::class, $types);
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

        $this->expectException(InvalidArgumentException::class);

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

        $this->expectException(InvalidArgumentException::class);

        $formats->matching('text/plain');
    }
}
