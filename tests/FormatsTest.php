<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client;

use Innmind\Rest\Client\{
    Formats,
    Format\Format,
    Format\MediaType
};
use Innmind\Immutable\{
    Map,
    Set,
    SetInterface
};
use PHPUnit\Framework\TestCase;

class FormatsTest extends TestCase
{
    public function testInterface()
    {
        $formats = new Formats(
            $all = (new Map('string', Format::class))
                ->put(
                    'json',
                    $format = new Format(
                        'json',
                        (new Set(MediaType::class))
                            ->add(new MediaType('application/json', 42)),
                        42
                    )
                )
        );

        $this->assertSame($all, $formats->all());
        $this->assertSame($format, $formats->get('json'));
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidMapKey()
    {
        new Formats(new Map('int', Format::class));
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidMapValue()
    {
        new Formats(new Map('string', 'string'));
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenEmptyMap()
    {
        new Formats(new Map('string', Format::class));
    }

    public function testMediaTypes()
    {
        $formats = new Formats(
            (new Map('string', Format::class))
                ->put(
                    'json',
                    new Format(
                        'json',
                        (new Set(MediaType::class))
                            ->add($json = new MediaType('application/json', 42)),
                        42
                    )
                )
                ->put(
                    'html',
                    new Format(
                        'html',
                        (new Set(MediaType::class))
                            ->add($html = new MediaType('text/html', 40))
                            ->add($xhtml = new MediaType('text/xhtml', 0)),
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
            (new Map('string', Format::class))
                ->put(
                    'json',
                    $json = new Format(
                        'json',
                        (new Set(MediaType::class))
                            ->add(new MediaType('application/json', 42)),
                        42
                    )
                )
                ->put(
                    'html',
                    $html = new Format(
                        'html',
                        (new Set(MediaType::class))
                            ->add(new MediaType('text/html', 40))
                            ->add(new MediaType('text/xhtml', 0)),
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
            (new Map('string', Format::class))
                ->put(
                    'html',
                    new Format(
                        'html',
                        (new Set(MediaType::class))
                            ->add(new MediaType('text/html', 40))
                            ->add(new MediaType('text/xhtml', 0)),
                        0
                    )
                )
        );

        $formats->fromMediaType('application/json');
    }

    public function testMatching()
    {
        $formats = new Formats(
            (new Map('string', Format::class))
                ->put(
                    'json',
                    new Format(
                        'json',
                        (new Set(MediaType::class))
                            ->add(new MediaType('application/json', 42)),
                        42
                    )
                )
                ->put(
                    'html',
                    $html = new Format(
                        'html',
                        (new Set(MediaType::class))
                            ->add(new MediaType('text/html', 40))
                            ->add(new MediaType('text/xhtml', 0)),
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
            (new Map('string', Format::class))
                ->put(
                    'json',
                    $json = new Format(
                        'json',
                        (new Set(MediaType::class))
                            ->add(new MediaType('application/json', 42)),
                        42
                    )
                )
                ->put(
                    'html',
                    new Format(
                        'html',
                        (new Set(MediaType::class))
                            ->add(new MediaType('text/html', 40))
                            ->add(new MediaType('text/xhtml', 0)),
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
            (new Map('string', Format::class))
                ->put(
                    'json',
                    new Format(
                        'json',
                        (new Set(MediaType::class))
                            ->add(new MediaType('application/json', 42)),
                        42
                    )
                )
                ->put(
                    'html',
                    new Format(
                        'html',
                        (new Set(MediaType::class))
                            ->add(new MediaType('text/html', 40))
                            ->add(new MediaType('text/xhtml', 0)),
                        0
                    )
                )
        );

        $formats->matching('text/plain');
    }
}
