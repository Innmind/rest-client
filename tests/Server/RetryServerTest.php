<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server;

use Innmind\Rest\Client\{
    Server\RetryServer,
    Server\Capabilities,
    Server,
    Identity,
    HttpResource,
    HttpResource\Property,
    Request\Range,
    Link,
    Exception\NormalizationException,
    Exception\DenormalizationException,
};
use Innmind\HttpTransport\Exception\ClientError;
use Innmind\Http\Message\{
    Request,
    Response,
    StatusCode\StatusCode,
};
use Innmind\Url\UrlInterface;
use Innmind\Specification\Specification;
use Innmind\Immutable\{
    SetInterface,
    Map,
    Set,
};
use PHPUnit\Framework\TestCase;

class RetryServerTest extends TestCase
{
    private $server;
    private $inner;

    public function setUp(): void
    {
        $this->server = new RetryServer(
            $this->inner = $this->createMock(Server::class)
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Server::class,
            $this->server
        );
    }

    public function testAll()
    {
        $specification = $this->createMock(Specification::class);
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

    /**
     * @dataProvider exceptions
     */
    public function testRetryAll(\Throwable $e)
    {
        $specification = $this->createMock(Specification::class);
        $range = new Range(0, 42);
        $this
            ->inner
            ->expects($this->at(0))
            ->method('all')
            ->with('foo', $specification, $range)
            ->will(
                $this->throwException($e)
            );
        $this
            ->inner
            ->expects($this->at(1))
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(Capabilities::class)
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

    public function testDoesntRetryAll()
    {
        $specification = $this->createMock(Specification::class);
        $range = new Range(0, 42);
        $this
            ->inner
            ->expects($this->once())
            ->method('all')
            ->with('foo', $specification, $range)
            ->will(
                $this->throwException(new \Exception)
            );
        $this
            ->inner
            ->expects($this->never())
            ->method('capabilities');

        $this->expectException(\Exception::class);

        $this->server->all('foo', $specification, $range);
    }

    public function testRead()
    {
        $identity = $this->createMock(Identity::class);
        $this
            ->inner
            ->expects($this->once())
            ->method('read')
            ->with('foo', $identity)
            ->willReturn(
                $expected = HttpResource::of('foo')
            );

        $resource = $this->server->read('foo', $identity);

        $this->assertSame($expected, $resource);
    }

    /**
     * @dataProvider exceptions
     */
    public function testRetryRead(\Throwable $e)
    {
        $identity = $this->createMock(Identity::class);
        $this
            ->inner
            ->expects($this->at(0))
            ->method('read')
            ->with('foo', $identity)
            ->will(
                $this->throwException($e)
            );
        $this
            ->inner
            ->expects($this->at(1))
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(Capabilities::class)
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
                $expected = HttpResource::of('foo')
            );

        $resource = $this->server->read('foo', $identity);

        $this->assertSame($expected, $resource);
    }

    public function testDoesntRetryRead()
    {
        $identity = $this->createMock(Identity::class);
        $this
            ->inner
            ->expects($this->once())
            ->method('read')
            ->with('foo', $identity)
            ->will(
                $this->throwException(new \Exception)
            );
        $this
            ->inner
            ->expects($this->never())
            ->method('capabilities');

        $this->expectException(\Exception::class);

        $this->server->read('foo', $identity);
    }

    public function testCreate()
    {
        $resource = HttpResource::of('foo');
        $this
            ->inner
            ->expects($this->once())
            ->method('create')
            ->with($resource)
            ->willReturn(
                $expected = $this->createMock(Identity::class)
            );

        $identity = $this->server->create($resource);

        $this->assertSame($expected, $identity);
    }

    /**
     * @dataProvider exceptions
     */
    public function testRetryCreate(\Throwable $e)
    {
        $resource = HttpResource::of('foo');
        $this
            ->inner
            ->expects($this->at(0))
            ->method('create')
            ->with($resource)
            ->will(
                $this->throwException($e)
            );
        $this
            ->inner
            ->expects($this->at(1))
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(Capabilities::class)
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
                $expected = $this->createMock(Identity::class)
            );

        $identity = $this->server->create($resource);

        $this->assertSame($expected, $identity);
    }

    public function testDoesntRetryCreate()
    {
        $resource = HttpResource::of('foo');
        $this
            ->inner
            ->expects($this->once())
            ->method('create')
            ->with($resource)
            ->will(
                $this->throwException(new \Exception)
            );
        $this
            ->inner
            ->expects($this->never())
            ->method('capabilities');

        $this->expectException(\Exception::class);

        $this->server->create($resource);
    }

    public function testUpdate()
    {
        $identity = $this->createMock(Identity::class);
        $resource = HttpResource::of('foo');
        $this
            ->inner
            ->expects($this->once())
            ->method('update')
            ->with($identity, $resource)
            ->willReturn($this->inner);

        $return = $this->server->update($identity, $resource);

        $this->assertSame($this->server, $return);
    }

    /**
     * @dataProvider exceptions
     */
    public function testRetryUpdate(\Throwable $e)
    {
        $identity = $this->createMock(Identity::class);
        $resource = HttpResource::of('foo');
        $this
            ->inner
            ->expects($this->at(0))
            ->method('update')
            ->with($identity, $resource)
            ->will(
                $this->throwException($e)
            );
        $this
            ->inner
            ->expects($this->at(1))
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(Capabilities::class)
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

    public function testDoesntRetryUpdate()
    {
        $identity = $this->createMock(Identity::class);
        $resource = HttpResource::of('foo');
        $this
            ->inner
            ->expects($this->once())
            ->method('update')
            ->with($identity, $resource)
            ->will(
                $this->throwException(new \Exception)
            );
        $this
            ->inner
            ->expects($this->never())
            ->method('capabilities');

        $this->expectException(\Exception::class);

        $this->server->update($identity, $resource);
    }

    public function testRemove()
    {
        $identity = $this->createMock(Identity::class);
        $this
            ->inner
            ->expects($this->once())
            ->method('remove')
            ->with('foo', $identity)
            ->willReturn($this->inner);

        $return = $this->server->remove('foo', $identity);

        $this->assertSame($this->server, $return);
    }

    /**
     * @dataProvider exceptions
     */
    public function testRetryRemove(\Throwable $e)
    {
        $identity = $this->createMock(Identity::class);
        $this
            ->inner
            ->expects($this->at(0))
            ->method('remove')
            ->with('foo', $identity)
            ->will(
                $this->throwException($e)
            );
        $this
            ->inner
            ->expects($this->at(1))
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(Capabilities::class)
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

    public function testDoesntRetryRemove()
    {
        $identity = $this->createMock(Identity::class);
        $this
            ->inner
            ->expects($this->once())
            ->method('remove')
            ->with('foo', $identity)
            ->will(
                $this->throwException(new \Exception)
            );
        $this
            ->inner
            ->expects($this->never())
            ->method('capabilities');

        $this->expectException(\Exception::class);

        $this->server->remove('foo', $identity);
    }

    public function testCapabilities()
    {
        $this
            ->inner
            ->expects($this->once())
            ->method('capabilities')
            ->willReturn(
                $expected = $this->createMock(Capabilities::class)
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

    public function testLink()
    {
        $identity = $this->createMock(Identity::class);
        $links = new Set(Link::class);
        $this
            ->inner
            ->expects($this->once())
            ->method('link')
            ->with('foo', $identity, $links);

        $this->assertSame(
            $this->server,
            $this->server->link('foo', $identity, $links)
        );
    }

    /**
     * @dataProvider exceptions
     */
    public function testRetryLink(\Throwable $e)
    {
        $identity = $this->createMock(Identity::class);
        $links = new Set(Link::class);
        $this
            ->inner
            ->expects($this->at(0))
            ->method('link')
            ->with('foo', $identity, $links)
            ->will($this->throwException($e));
        $this
            ->inner
            ->expects($this->at(1))
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(Capabilities::class)
            );
        $capabilities
            ->expects($this->once())
            ->method('refresh');
        $this
            ->inner
            ->expects($this->at(2))
            ->method('link')
            ->with('foo', $identity, $links);

        $this->assertSame(
            $this->server,
            $this->server->link('foo', $identity, $links)
        );
    }

    public function testDoesntRetryLink()
    {
        $identity = $this->createMock(Identity::class);
        $links = new Set(Link::class);
        $this
            ->inner
            ->expects($this->once())
            ->method('link')
            ->with('foo', $identity, $links)
            ->will($this->throwException(new \Exception));
        $this
            ->inner
            ->expects($this->never())
            ->method('capabilities');

        $this->expectException(\Exception::class);

        $this->server->link('foo', $identity, $links);
    }

    public function testUnlink()
    {
        $identity = $this->createMock(Identity::class);
        $links = new Set(Link::class);
        $this
            ->inner
            ->expects($this->once())
            ->method('unlink')
            ->with('foo', $identity, $links);

        $this->assertSame(
            $this->server,
            $this->server->unlink('foo', $identity, $links)
        );
    }

    /**
     * @dataProvider exceptions
     */
    public function testRetryUnlink(\Throwable $e)
    {
        $identity = $this->createMock(Identity::class);
        $links = new Set(Link::class);
        $this
            ->inner
            ->expects($this->at(0))
            ->method('unlink')
            ->with('foo', $identity, $links)
            ->will($this->throwException($e));
        $this
            ->inner
            ->expects($this->at(1))
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(Capabilities::class)
            );
        $capabilities
            ->expects($this->once())
            ->method('refresh');
        $this
            ->inner
            ->expects($this->at(2))
            ->method('unlink')
            ->with('foo', $identity, $links);

        $this->assertSame(
            $this->server,
            $this->server->unlink('foo', $identity, $links)
        );
    }

    public function testDoesntRetryUnlink()
    {
        $identity = $this->createMock(Identity::class);
        $links = new Set(Link::class);
        $this
            ->inner
            ->expects($this->once())
            ->method('unlink')
            ->with('foo', $identity, $links)
            ->will($this->throwException(new \Exception));
        $this
            ->inner
            ->expects($this->never())
            ->method('capabilities');

        $this->expectException(\Exception::class);

        $this->server->unlink('foo', $identity, $links);
    }

    public function exceptions(): array
    {
        return [
            [$this->createException()],
            [new NormalizationException],
            [new DenormalizationException],
        ];
    }

    private function createException(): ClientError
    {
        $exception = new ClientError(
            $this->createMock(Request::class),
            $response = $this->createMock(Response::class)
        );
        $response
            ->expects($this->once())
            ->method('statusCode')
            ->willReturn(new StatusCode(400)); //bad request

        return $exception;
    }
}
