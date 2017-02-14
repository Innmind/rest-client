<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server;

use Innmind\Rest\Client\{
    Server\RetryServer,
    Server\CapabilitiesInterface,
    ServerInterface,
    IdentityInterface,
    HttpResource,
    HttpResource\Property,
    Request\Range
};
use Innmind\Url\UrlInterface;
use Innmind\Specification\SpecificationInterface;
use Innmind\Immutable\{
    SetInterface,
    Map
};
use PHPUnit\Framework\TestCase;

class RetryServerTest extends TestCase
{
    private $server;
    private $inner;

    public function setUp()
    {
        $this->server = new RetryServer(
            $this->inner = $this->createMock(ServerInterface::class)
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            ServerInterface::class,
            $this->server
        );
    }

    public function testAll()
    {
        $specification = $this->createMock(SpecificationInterface::class);
        $range = new Range(0, 42);
        $this
            ->inner
            ->expects($this->once())
            ->method('all')
            ->with('foo', $specification, $range)
            ->willReturn($expected = $this->createMock(SetInterface::class));

        $identities = $this->server->all('foo', $specification, $range);

        $this->assertSame($expected, $identities);
    }

    public function testRetryAll()
    {
        $specification = $this->createMock(SpecificationInterface::class);
        $range = new Range(0, 42);
        $this
            ->inner
            ->expects($this->at(0))
            ->method('all')
            ->with('foo', $specification, $range)
            ->will(
                $this->throwException(new \Exception)
            );
        $this
            ->inner
            ->expects($this->at(1))
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(CapabilitiesInterface::class)
            );
        $capabilities
            ->expects($this->once())
            ->method('refresh');
        $this
            ->inner
            ->expects($this->at(2))
            ->method('all')
            ->with('foo', $specification, $range)
            ->willReturn($expected = $this->createMock(SetInterface::class));

        $identities = $this->server->all('foo', $specification, $range);

        $this->assertSame($expected, $identities);
    }

    public function testRead()
    {
        $identity = $this->createMock(IdentityInterface::class);
        $this
            ->inner
            ->expects($this->once())
            ->method('read')
            ->with('foo', $identity)
            ->willReturn(
                $expected = new HttpResource(
                    'foo',
                    new Map('string', Property::class)
                )
            );

        $resource = $this->server->read('foo', $identity);

        $this->assertSame($expected, $resource);
    }

    public function testRetryRead()
    {
        $identity = $this->createMock(IdentityInterface::class);
        $this
            ->inner
            ->expects($this->at(0))
            ->method('read')
            ->with('foo', $identity)
            ->will(
                $this->throwException(new \Exception)
            );
        $this
            ->inner
            ->expects($this->at(1))
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(CapabilitiesInterface::class)
            );
        $capabilities
            ->expects($this->once())
            ->method('refresh');
        $this
            ->inner
            ->expects($this->at(2))
            ->method('read')
            ->with('foo', $identity)
            ->willReturn(
                $expected = new HttpResource(
                    'foo',
                    new Map('string', Property::class)
                )
            );

        $resource = $this->server->read('foo', $identity);

        $this->assertSame($expected, $resource);
    }

    public function testCreate()
    {
        $resource = new HttpResource(
            'foo',
            new Map('string', Property::class)
        );
        $this
            ->inner
            ->expects($this->once())
            ->method('create')
            ->with($resource)
            ->willReturn(
                $expected = $this->createMock(IdentityInterface::class)
            );

        $identity = $this->server->create($resource);

        $this->assertSame($expected, $identity);
    }

    public function testRetryCreate()
    {
        $resource = new HttpResource(
            'foo',
            new Map('string', Property::class)
        );
        $this
            ->inner
            ->expects($this->at(0))
            ->method('create')
            ->with($resource)
            ->will(
                $this->throwException(new \Exception)
            );
        $this
            ->inner
            ->expects($this->at(1))
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(CapabilitiesInterface::class)
            );
        $capabilities
            ->expects($this->once())
            ->method('refresh');
        $this
            ->inner
            ->expects($this->at(2))
            ->method('create')
            ->with($resource)
            ->willReturn(
                $expected = $this->createMock(IdentityInterface::class)
            );

        $identity = $this->server->create($resource);

        $this->assertSame($expected, $identity);
    }

    public function testUpdate()
    {
        $identity = $this->createMock(IdentityInterface::class);
        $resource = new HttpResource(
            'foo',
            new Map('string', Property::class)
        );
        $this
            ->inner
            ->expects($this->once())
            ->method('update')
            ->with($identity, $resource)
            ->willReturn($this->inner);

        $return = $this->server->update($identity, $resource);

        $this->assertSame($this->server, $return);
    }

    public function testRetryUpdate()
    {
        $identity = $this->createMock(IdentityInterface::class);
        $resource = new HttpResource(
            'foo',
            new Map('string', Property::class)
        );
        $this
            ->inner
            ->expects($this->at(0))
            ->method('update')
            ->with($identity, $resource)
            ->will(
                $this->throwException(new \Exception)
            );
        $this
            ->inner
            ->expects($this->at(1))
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(CapabilitiesInterface::class)
            );
        $capabilities
            ->expects($this->once())
            ->method('refresh');
        $this
            ->inner
            ->expects($this->at(2))
            ->method('update')
            ->with($identity, $resource)
            ->willReturn($this->inner);

        $return = $this->server->update($identity, $resource);

        $this->assertSame($this->server, $return);
    }

    public function testRemove()
    {
        $identity = $this->createMock(IdentityInterface::class);
        $this
            ->inner
            ->expects($this->once())
            ->method('remove')
            ->with('foo', $identity)
            ->willReturn($this->inner);

        $return = $this->server->remove('foo', $identity);

        $this->assertSame($this->server, $return);
    }

    public function testRetryRemove()
    {
        $identity = $this->createMock(IdentityInterface::class);
        $this
            ->inner
            ->expects($this->at(0))
            ->method('remove')
            ->with('foo', $identity)
            ->will(
                $this->throwException(new \Exception)
            );
        $this
            ->inner
            ->expects($this->at(1))
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(CapabilitiesInterface::class)
            );
        $capabilities
            ->expects($this->once())
            ->method('refresh');
        $this
            ->inner
            ->expects($this->at(2))
            ->method('remove')
            ->with('foo', $identity)
            ->willReturn($this->inner);

        $return = $this->server->remove('foo', $identity);

        $this->assertSame($this->server, $return);
    }

    public function testCapabilities()
    {
        $this
            ->inner
            ->expects($this->once())
            ->method('capabilities')
            ->willReturn(
                $expected = $this->createMock(CapabilitiesInterface::class)
            );

        $this->assertSame(
            $expected,
            $this->server->capabilities()
        );
    }

    public function testUrl()
    {
        $this
            ->inner
            ->expects($this->once())
            ->method('url')
            ->willReturn(
                $expected = $this->createMock(UrlInterface::class)
            );

        $this->assertSame(
            $expected,
            $this->server->url()
        );
    }
}
