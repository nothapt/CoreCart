<?php
declare(strict_types=1);

namespace CoreCart\Tests\Unit;

use PHPUnit\Framework\TestCase;
use CoreCart\System\Engine\Response;
use CoreCart\System\Engine\JsonResponse;
use CoreCart\System\Engine\RedirectResponse;

class ResponseTest extends TestCase
{
    public function testResponseDefaults(): void
    {
        $response = new Response();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('', $response->getBody());
        $this->assertSame([], $response->getHeaders());
        $this->assertSame([], $response->getCookies());
    }

    public function testSetStatusCode(): void
    {
        $response = new Response();
        $response->setStatusCode(404);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testSetBody(): void
    {
        $response = new Response();
        $response->setBody('Hello');
        $this->assertSame('Hello', $response->getBody());
    }

    public function testSetHeader(): void
    {
        $response = new Response();
        $response->setHeader('X-Custom', 'value');
        $this->assertSame(['X-Custom' => 'value'], $response->getHeaders());
    }

    public function testAddCookie(): void
    {
        $response = new Response();
        $response->addCookie('session', 'abc', time() + 3600, '/', '', true, true, 'Strict');

        $cookies = $response->getCookies();
        $this->assertArrayHasKey('session', $cookies);
        $this->assertSame('abc', $cookies['session']['value']);
        $this->assertSame('Strict', $cookies['session']['sameSite']);
    }

    public function testJsonResponseSuccess(): void
    {
        $response = JsonResponse::success(['id' => 1], 'Created', 201);
        $this->assertSame(201, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        $this->assertSame('success', $body['status']);
        $this->assertSame('Created', $body['message']);
        $this->assertSame(['id' => 1], $body['data']);
    }

    public function testJsonResponseError(): void
    {
        $response = JsonResponse::error('Not found', 404, 'NOT_FOUND');
        $this->assertSame(404, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        $this->assertSame('error', $body['status']);
        $this->assertSame('NOT_FOUND', $body['code']);
        $this->assertSame('Not found', $body['message']);
    }

    public function testJsonResponseValidationErrors(): void
    {
        $response = JsonResponse::validationErrors(['email' => ['Required']], 'Invalid');
        $this->assertSame(422, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        $this->assertSame('VALIDATION_ERROR', $body['code']);
        $this->assertSame(['email' => ['Required']], $body['fields']);
    }

    public function testJsonResponseContentType(): void
    {
        $response = JsonResponse::success();
        $headers = $response->getHeaders();
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertStringContainsString('application/json', $headers['Content-Type']);
    }

    public function testRedirectResponse(): void
    {
        $response = RedirectResponse::to('/login');
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login', $response->getHeaders()['Location']);
    }

    public function testRedirectResponsePermanent(): void
    {
        $response = RedirectResponse::permanent('/new');
        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('/new', $response->getHeaders()['Location']);
    }

    public function testRedirectResponseBack(): void
    {
        $request = new \CoreCart\System\Engine\Request(
            headers: ['referer' => '/previous-page']
        );
        $response = RedirectResponse::back($request);
        $this->assertSame('/previous-page', $response->getHeaders()['Location']);
    }

    public function testRedirectResponseBackDefault(): void
    {
        $request = new \CoreCart\System\Engine\Request();
        $response = RedirectResponse::back($request);
        $this->assertSame('/', $response->getHeaders()['Location']);
    }
}
