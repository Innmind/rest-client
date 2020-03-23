<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Translator\Specification;

use Innmind\Rest\Client\{
    Translator\Specification\SpecificationTranslator,
    Translator\SpecificationTranslator as SpecificationTranslatorInterface,
    Exception\OnlyEqualityCanBeTranslated,
    Exception\OnlyAndCompositionCanBeTranslated,
    Exception\SpecificationCantBeTranslated,
};
use Innmind\Specification\{
    Comparator,
    Composite,
    Operator,
    Not,
    Specification,
    Sign,
};
use PHPUnit\Framework\TestCase;

class SpecificationTranslatorTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            SpecificationTranslatorInterface::class,
            new SpecificationTranslator
        );
    }

    public function testTranslateComparator()
    {
        $spec = $this->createMock(Comparator::class);
        $spec
            ->expects($this->once())
            ->method('property')
            ->willReturn('bar');
        $spec
            ->expects($this->once())
            ->method('sign')
            ->willReturn(Sign::equality());
        $spec
            ->expects($this->once())
            ->method('value')
            ->willReturn('baz');

        $query = (new SpecificationTranslator)($spec);

        $this->assertSame('bar=baz', $query);
    }

    public function testThrowWhenUnsupportedComparison()
    {
        $spec = $this->createMock(Comparator::class);
        $spec
            ->expects($this->never())
            ->method('property');
        $spec
            ->expects($this->once())
            ->method('sign')
            ->willReturn(Sign::moreThan());
        $spec
            ->expects($this->never())
            ->method('value');

        $this->expectException(OnlyEqualityCanBeTranslated::class);

        (new SpecificationTranslator)($spec);
    }

    public function testTranslateComposite()
    {
        $left = $this->createMock(Comparator::class);
        $left
            ->expects($this->once())
            ->method('property')
            ->willReturn('bar');
        $left
            ->expects($this->once())
            ->method('sign')
            ->willReturn(Sign::equality());
        $left
            ->expects($this->once())
            ->method('value')
            ->willReturn('baz');
        $right = $this->createMock(Comparator::class);
        $right
            ->expects($this->once())
            ->method('property')
            ->willReturn('foo');
        $right
            ->expects($this->once())
            ->method('sign')
            ->willReturn(Sign::equality());
        $right
            ->expects($this->once())
            ->method('value')
            ->willReturn('foobar');
        $spec = $this->createMock(Composite::class);
        $spec
            ->expects($this->once())
            ->method('left')
            ->willReturn($left);
        $spec
            ->expects($this->once())
            ->method('right')
            ->willReturn($right);
        $spec
            ->expects($this->once())
            ->method('operator')
            ->willReturn(Operator::and());

        $query = (new SpecificationTranslator)($spec);

        $this->assertSame('bar=baz&foo=foobar', $query);
    }

    public function testThrowWhenUnsupportedComposite()
    {
        $spec = $this->createMock(Composite::class);
        $spec
            ->expects($this->never())
            ->method('left');
        $spec
            ->expects($this->never())
            ->method('right');
        $spec
            ->expects($this->once())
            ->method('operator')
            ->willReturn(Operator::or());

        $this->expectException(OnlyAndCompositionCanBeTranslated::class);

        (new SpecificationTranslator)($spec);
    }

    public function testThrowWhenTranslatingNegativeSpecification()
    {
        $this->expectException(SpecificationCantBeTranslated::class);

        (new SpecificationTranslator)(
            $this->createMock(Not::class)
        );
    }

    public function testThrowWhenTranslatingUnknownSpecification()
    {
        $this->expectException(SpecificationCantBeTranslated::class);

        (new SpecificationTranslator)(
            $this->createMock(Specification::class)
        );
    }
}
