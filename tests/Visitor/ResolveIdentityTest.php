<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Visitor;

use Innmind\Rest\Client\Visitor\ResolveIdentity;
use Innmind\UrlResolver\UrlResolver;
use Innmind\Url\Url;
use PHPUnit\Framework\TestCase;

class ResolveIdentityTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function testResolveIdentity($source, $destination, $expected)
    {
        $resolve = new ResolveIdentity(
            new UrlResolver
        );

        $this->assertSame(
            $expected,
            $resolve(
                Url::of($source),
                Url::of($destination)
            )
        );
    }

    public function cases(): array
    {
        return [
            ['http://example.com/foo', '/foo/42', '42'],
            ['http://example.com/foo/', '/foo/42', '42'],
            ['http://example.com/foo', 'http://example.com/foo/42/66', '42/66'],
            ['http://example.com/foo/', 'http://example.com/foo/42/66', '42/66'],
        ];
    }
}
