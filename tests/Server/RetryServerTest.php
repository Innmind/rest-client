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
    StatusCode,
};
use Innmind\Url\Url;
use Innmind\Specification\Specification;
use Innmind\Immutable\{
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
            ->willReturn($expected = Set::of(Identity::class));

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
            ->expects($this->exactly(2))
            ->method('all')
            ->with('foo', $specification, $range)
            ->will($this->onConsecutiveCalls(
                $this->throwException($e),
                $expected = Set::of(Identity::class),
            ));
        $this
            ->inner
            ->expects($this->once())
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(Capabilities::class)
            );
        $capabilities
            ->expects($this->once())
            ->method('refresh');

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
            ->expects($this->exactly(2))
            ->method('read')
            ->with('foo', $identity)
            ->will($this->onConsecutiveCalls(
                $this->throwException($e),
                $expected = HttpResource::of('foo'),
            ));
        $this
            ->inner
            ->expects($this->once())
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(Capabilities::class)
            );
        $capabilities
            ->expects($this->once())
            ->method('refresh');

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
            ->expects($this->exactly(2))
            ->method('create')
            ->with($resource)
            ->will($this->onConsecutiveCalls(
                $this->throwException($e),
                $expected = $this->createMock(Identity::class),
            ));
        $this
            ->inner
            ->expects($this->once())
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(Capabilities::class)
            );
        $capabilities
            ->expects($this->once())
            ->method('refresh');

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
            ->with($identity, $resource);

        $return = $this->server->update($identity, $resource);

        $this->assertNull($return);
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
            ->expects($this->exactly(2))
            ->method('update')
            ->with($identity, $resource)
            ->will($this->onConsecutiveCalls(
                $this->throwException($e)
            ));
        $this
            ->inner
            ->expects($this->once())
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(Capabilities::class)
            );
        $capabilities
            ->expects($this->once())
            ->method('refresh');

        $return = $this->server->update($identity, $resource);

        $this->assertNull($return);
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
            ->with('foo', $identity);

        $return = $this->server->remove('foo', $identity);

        $this->assertNull($return);
    }

    /**
     * @dataProvider exceptions
     */
    public function testRetryRemove(\Throwable $e)
    {
        $identity = $this->createMock(Identity::class);
        $this
            ->inner
            ->expects($this->exactly(2))
            ->method('remove')
            ->with('foo', $identity)
            ->will($this->onConsecutiveCalls(
                $this->throwException($e)
            ));
        $this
            ->inner
            ->expects($this->once())
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(Capabilities::class)
            );
        $capabilities
            ->expects($this->once())
            ->method('refresh');

        $return = $this->server->remove('foo', $identity);

        $this->assertNull($return);
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
                $expected = Url::of('http://example.com/')
            );

        $this->assertSame(
            $expected,
            $this->server->url()
        );
    }

    public function testLink()
    {
        $identity = $this->createMock(Identity::class);
        $links = Set::of(Link::class);
        $this
            ->inner
            ->expects($this->once())
            ->method('link')
            ->with('foo', $identity, $links);

        $this->assertNull(
            $this->server->link('foo', $identity, $links)
        );
    }

    /**
     * @dataProvider exceptions
     */
    public function testRetryLink(\Throwable $e)
    {
        $identity = $this->createMock(Identity::class);
        $links = Set::of(Link::class);
        $this
            ->inner
            ->expects($this->exactly(2))
            ->method('link')
            ->with('foo', $identity, $links)
            ->will($this->onConsecutiveCalls($this->throwException($e)));
        $this
            ->inner
            ->expects($this->once())
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(Capabilities::class)
            );
        $capabilities
            ->expects($this->once())
            ->method('refresh');

        $this->assertNull(
            $this->server->link('foo', $identity, $links)
        );
    }

    public function testDoesntRetryLink()
    {
        $identity = $this->createMock(Identity::class);
        $links = Set::of(Link::class);
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
        $links = Set::of(Link::class);
        $this
            ->inner
            ->expects($this->once())
            ->method('unlink')
            ->with('foo', $identity, $links);

        $this->assertNull(
            $this->server->unlink('foo', $identity, $links)
        );
    }

    /**
     * @dataProvider exceptions
     */
    public function testRetryUnlink(\Throwable $e)
    {
        $identity = $this->createMock(Identity::class);
        $links = Set::of(Link::class);
        $this
            ->inner
            ->expects($this->exactly(2))
            ->method('unlink')
            ->with('foo', $identity, $links)
            ->will($this->onConsecutiveCalls($this->throwException($e)));
        $this
            ->inner
            ->expects($this->once())
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(Capabilities::class)
            );
        $capabilities
            ->expects($this->once())
            ->method('refresh');

        $this->assertNull(
            $this->server->unlink('foo', $identity, $links)
        );
    }

    public function testDoesntRetryUnlink()
    {
        $identity = $this->createMock(Identity::class);
        $links = Set::of(Link::class);
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
