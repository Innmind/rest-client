<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\Definition\Access;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class AccessTest extends TestCase
{
    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidMask()
    {
        new Access(new Set('int'));
    }

    public function testIsReadable()
    {
        $this->assertTrue(
            (new Access(
                (new Set('string'))
                    ->add(Access::READ)
            ))
                ->isReadable()
        );
        $this->assertTrue(
            (new Access(
                (new Set('string'))
                    ->add(Access::READ)
                    ->add(Access::CREATE)
                    ->add(Access::UPDATE)
            ))
                ->isReadable()
        );
    }

    public function testIsCreatable()
    {
        $this->assertTrue(
            (new Access(
                (new Set('string'))
                    ->add(Access::CREATE)
            ))
                ->isCreatable()
        );
        $this->assertTrue(
            (new Access(
                (new Set('string'))
                    ->add(Access::READ)
                    ->add(Access::CREATE)
                    ->add(Access::UPDATE)
            ))
                ->isCreatable()
        );
    }

    public function testIsUpdatable()
    {
        $this->assertTrue(
            (new Access(
                (new Set('string'))
                    ->add(Access::UPDATE)
            ))
                ->isUpdatable()
        );
        $this->assertTrue(
            (new Access(
                (new Set('string'))
                    ->add(Access::READ)
                    ->add(Access::CREATE)
                    ->add(Access::UPDATE)
            ))
                ->isUpdatable()
        );
    }

    public function testMask()
    {
        $access = (new Access(
            $mask = (new Set('string'))
                ->add(Access::READ)
                ->add(Access::CREATE)
                ->add(Access::UPDATE)
        ));

        $this->assertSame($mask, $access->mask());
    }

    public function testMatches()
    {
        $access = (new Access(
            (new Set('string'))
                ->add(Access::READ)
                ->add(Access::CREATE)
                ->add(Access::UPDATE)
        ));
        $this->assertTrue($access->matches(
            new Access(
                (new Set('string'))->add(Access::READ)
            )
        ));
        $this->assertTrue($access->matches(
            new Access(
                (new Set('string'))->add(Access::CREATE)
            )
        ));
        $this->assertTrue($access->matches(
            new Access(
                (new Set('string'))->add(Access::UPDATE)
            )
        ));

        $access = (new Access(
            $mask = (new Set('string'))
                ->add(Access::READ)
        ));
        $this->assertTrue($access->matches(
            new Access(
                (new Set('string'))->add(Access::READ)
            )
        ));
        $this->assertFalse($access->matches(
            new Access(
                (new Set('string'))->add(Access::CREATE)
            )
        ));
        $this->assertFalse($access->matches(
            new Access(
                (new Set('string'))->add(Access::UPDATE)
            )
        ));
    }
}
