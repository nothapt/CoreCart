<?php
declare(strict_types=1);

namespace CoreCart\Tests\Unit;

use PHPUnit\Framework\TestCase;
use CoreCart\System\Engine\Request;
use CoreCart\System\Infrastructure\AuthenticatedUser;

class RequestTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $request = new Request();

        $this->assertSame('GET', $request->method);
        $this->assertSame('/', $request->path);
        $this->assertSame([], $request->queryParams);
        $this->assertSame([], $request->body);
        $this->assertNull($request->json);
        $this->assertSame([], $request->headers);
        $this->assertSame([], $request->cookies);
        $this->assertSame([], $request->files);
        $this->assertNull($request->user);
        $this->assertSame('', $request->requestId);
        $this->assertSame('0.0.0.0', $request->ipAddress);
        $this->assertNull($request->contentType);
        $this->assertSame(0, $request->contentLength);
        $this->assertSame('', $request->rawBody);
    }

    public function testConstructorWithValues(): void
    {
        $request = new Request(
            method: 'POST',
            path: '/api/test',
            queryParams: ['page' => '1'],
            body: ['name' => 'test'],
            json: ['name' => 'test'],
            headers: ['accept' => 'application/json'],
            cookies: ['session' => 'abc'],
            files: [],
            user: null,
            requestId: 'req-123',
            ipAddress: '127.0.0.1',
            contentType: 'application/json',
            contentLength: 14,
            rawBody: '{"name":"test"}'
        );

        $this->assertSame('POST', $request->method);
        $this->assertSame('/api/test', $request->path);
        $this->assertSame(['page' => '1'], $request->queryParams);
        $this->assertSame(['name' => 'test'], $request->body);
        $this->assertSame(['name' => 'test'], $request->json);
        $this->assertSame(['accept' => 'application/json'], $request->headers);
        $this->assertSame(['session' => 'abc'], $request->cookies);
        $this->assertSame('req-123', $request->requestId);
        $this->assertSame('127.0.0.1', $request->ipAddress);
        $this->assertSame('application/json', $request->contentType);
        $this->assertSame(14, $request->contentLength);
    }

    public function testWithUserReturnsNewInstance(): void
    {
        $request = new Request(method: 'GET', path: '/');
        $user = new AuthenticatedUser(id: 1, username: 'admin', email: 'admin@test.com', role: 'admin');

        $newRequest = $request->withUser($user);

        $this->assertNotSame($request, $newRequest);
        $this->assertNull($request->user);
        $this->assertNotNull($newRequest->user);
        $this->assertSame(1, $newRequest->user->id);
        $this->assertSame('admin', $newRequest->user->username);
    }

    public function testWithBodyReturnsNewInstance(): void
    {
        $request = new Request(method: 'POST', body: []);
        $newRequest = $request->withBody(['key' => 'value']);

        $this->assertNotSame($request, $newRequest);
        $this->assertSame([], $request->body);
        $this->assertSame(['key' => 'value'], $newRequest->body);
    }

    public function testGetUserId(): void
    {
        $request = new Request();
        $this->assertNull($request->getUserId());

        $user = new AuthenticatedUser(id: 42, username: 'test', email: 'test@test.com', role: 'customer');
        $request = $request->withUser($user);
        $this->assertSame(42, $request->getUserId());
    }

    public function testIsLoggedIn(): void
    {
        $request = new Request();
        $this->assertFalse($request->isLoggedIn());

        $user = new AuthenticatedUser(id: 1, username: 'test', email: 'test@test.com', role: 'admin');
        $request = $request->withUser($user);
        $this->assertTrue($request->isLoggedIn());
    }

    public function testIsGet(): void
    {
        $this->assertTrue((new Request(method: 'GET'))->isGet());
        $this->assertFalse((new Request(method: 'POST'))->isGet());
    }

    public function testIsPost(): void
    {
        $this->assertTrue((new Request(method: 'POST'))->isPost());
        $this->assertFalse((new Request(method: 'GET'))->isPost());
    }

    public function testGetQueryParamDefault(): void
    {
        $request = new Request(queryParams: ['page' => '2']);
        $this->assertSame('2', $request->getQueryParam('page'));
        $this->assertSame('default', $request->getQueryParam('missing', 'default'));
        $this->assertNull($request->getQueryParam('missing'));
    }

    public function testGetInputDefault(): void
    {
        $request = new Request(body: ['name' => 'test']);
        $this->assertSame('test', $request->getInput('name'));
        $this->assertSame('default', $request->getInput('missing', 'default'));
        $this->assertNull($request->getInput('missing'));
    }

    public function testGetJsonValue(): void
    {
        $request = new Request(json: ['name' => 'John', 'age' => 30]);
        $this->assertSame('John', $request->getJsonValue('name'));
        $this->assertSame(30, $request->getJsonValue('age'));
        $this->assertNull($request->getJsonValue('missing'));
        $this->assertSame('default', $request->getJsonValue('missing', 'default'));
    }

    public function testGetJsonValueReturnsNullForNonArray(): void
    {
        $request = new Request(json: 'string value');
        $this->assertNull($request->getJsonValue('any'));
    }

    public function testGetHeaderCaseInsensitive(): void
    {
        $request = new Request(headers: ['Content-Type' => 'application/json']);
        $this->assertSame('application/json', $request->getHeader('content-type'));
        $this->assertSame('application/json', $request->getHeader('CONTENT-TYPE'));
        $this->assertNull($request->getHeader('missing'));
        $this->assertSame('default', $request->getHeader('missing', 'default'));
    }

    public function testGetCookie(): void
    {
        $request = new Request(cookies: ['session' => 'abc123']);
        $this->assertSame('abc123', $request->getCookie('session'));
        $this->assertNull($request->getCookie('missing'));
    }

    public function testWantsJson(): void
    {
        $request = new Request(headers: ['accept' => 'text/html']);
        $this->assertFalse($request->wantsJson());

        $request = new Request(headers: ['accept' => 'application/json']);
        $this->assertTrue($request->wantsJson());

        $request = new Request(headers: ['x-requested-with' => 'XMLHttpRequest']);
        $this->assertTrue($request->wantsJson());
    }

    public function testImmutability(): void
    {
        $request1 = new Request(method: 'GET', path: '/test');
        $request2 = $request1->withBody(['key' => 'val']);

        // Original unchanged
        $this->assertSame('GET', $request1->method);
        $this->assertSame('/test', $request1->path);
        $this->assertSame([], $request1->body);

        // New instance has changes
        $this->assertSame(['key' => 'val'], $request2->body);
    }
}
