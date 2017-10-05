<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Identity;

use Innmind\Rest\Client\{
    Identity\Identity,
    Identity as IdentityInterface
};
use PHPUnit\Framework\TestCase;

class IdentityTest extends TestCase
{
    public function testInterface()
    {
        $identity = new Identity('foo');

        $this->assertInstanceOf(IdentityInterface::class, $identity);
        $this->assertSame('foo', (string) $identity);
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\DomainException
     */
    public function testThrowWhenEmptyValue()
    {
        new Identity('');
    }
}
