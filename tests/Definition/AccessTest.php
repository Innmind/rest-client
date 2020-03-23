<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\Definition\Access;
use Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class AccessTest extends TestCase
{
    public function testIsReadable()
    {
        $this->assertTrue(
            (new Access(Access::READ))->isReadable()
        );
        $this->assertTrue(
            (new Access(
                Access::READ,
                Access::CREATE,
                Access::UPDATE
            ))
                ->isReadable()
        );
    }

    public function testIsCreatable()
    {
        $this->assertTrue(
            (new Access(Access::CREATE))->isCreatable()
        );
        $this->assertTrue(
            (new Access(
                Access::READ,
                Access::CREATE,
                Access::UPDATE
            ))
                ->isCreatable()
        );
    }

    public function testIsUpdatable()
    {
        $this->assertTrue(
            (new Access(Access::UPDATE))->isUpdatable()
        );
        $this->assertTrue(
            (new Access(
                Access::READ,
                Access::CREATE,
                Access::UPDATE
            ))
                ->isUpdatable()
        );
    }

    public function testMask()
    {
        $access = new Access(
            Access::READ,
            Access::CREATE,
            Access::UPDATE
        );

        $this->assertInstanceOf(Set::class, $access->mask());
        $this->assertSame('string', (string) $access->mask()->type());
        $this->assertSame(
            [Access::READ, Access::CREATE, Access::UPDATE],
            unwrap($access->mask())
        );
    }

    public function testMatches()
    {
        $access = new Access(
            Access::READ,
            Access::CREATE,
            Access::UPDATE
        );
        $this->assertTrue($access->matches(
            new Access(Access::READ)
        ));
        $this->assertTrue($access->matches(
            new Access(Access::CREATE)
        ));
        $this->assertTrue($access->matches(
            new Access(Access::UPDATE)
        ));

        $access = new Access(Access::READ);
        $this->assertTrue($access->matches(
            new Access(Access::READ)
        ));
        $this->assertFalse($access->matches(
            new Access(Access::CREATE)
        ));
        $this->assertFalse($access->matches(
            new Access(Access::UPDATE)
        ));
    }
}
