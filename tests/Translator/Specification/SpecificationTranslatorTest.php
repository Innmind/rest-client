<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Translator\Specification;

use Innmind\Rest\Client\Translator\{
    Specification\SpecificationTranslator,
    SpecificationTranslator as SpecificationTranslatorInterface,
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

        $query = (new SpecificationTranslator)->translate($spec);

        $this->assertSame('bar=baz', $query);
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\OnlyEqualityCanBeTranslated
     */
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

        (new SpecificationTranslator)->translate($spec);
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

        $query = (new SpecificationTranslator)->translate($spec);

        $this->assertSame('bar=baz&foo=foobar', $query);
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\OnlyAndCompositionCanBeTranslated
     */
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

        (new SpecificationTranslator)->translate($spec);
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\SpecificationCantBeTranslated
     */
    public function testThrowWhenTranslatingNegativeSpecification()
    {
        (new SpecificationTranslator)->translate(
            $this->createMock(Not::class)
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\SpecificationCantBeTranslated
     */
    public function testThrowWhenTranslatingUnknownSpecification()
    {
        (new SpecificationTranslator)->translate(
            $this->createMock(Specification::class)
        );
    }
}
