<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\Definition\Identity;
use PHPUnit\Framework\TestCase;

class IdentityTest extends TestCase
{
    /**
     * @expectedException Innmind\Rest\Client\Exception\DomainException
     */
    public function testThrowWhenEmptyIdentity()
    {
        new Identity('');
    }

    public function testInterface()
    {
        $this->assertSame(
            'foo',
            (string) new Identity('foo')
        );
    }
}
