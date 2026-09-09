<?php

declare(strict_types=1);

namespace Nexora\Sdk\Tests\Unit;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Nexora\Sdk\Exception\AuthenticationException;
use Nexora\Sdk\Exception\NetworkException;
use Nexora\Sdk\Exception\NexoraException;
use Nexora\Sdk\Exception\PermissionException;
use Nexora\Sdk\Exception\RateLimitException;
use Nexora\Sdk\Exception\ValidationException;
use Nexora\Sdk\Nexora;
use PHPUnit\Framework\TestCase;

final class ErrorMappingTest extends TestCase
{
    private function createMockClient(array $responses): Nexora
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $guzzle = new GuzzleClient(['handler' => $handlerStack]);

        return new Nexora(
            apiKey: 'nx_test_error_test_key_123',
            baseUrl: 'https://api.nexoragms.com/developer/v1',
            httpClient: $guzzle
        );
    }

    public function testMaps401ToAuthenticationExceptionWithRequestId(): void
    {
        $mockResponse = new Response(
            401,
            ['Content-Type' => 'application/json', 'X-Request-Id' => 'req_auth_header_999'],
            (string) json_encode([
                'error' => [
                    'code' => 'API_KEY_INVALID',
                    'message' => 'Invalid API key credentials provided.',
                    'requestId' => 'req_auth_body_999',
                ],
            ])
        );

        $nexora = $this->createMockClient([$mockResponse]);

        try {
            $nexora->modules()->list();
            $this->fail('Expected AuthenticationException was not thrown');
        } catch (AuthenticationException $e) {
            $this->assertSame(401, $e->getStatusCode());
            $this->assertSame('API_KEY_INVALID', $e->getErrorCode());
            $this->assertSame('req_auth_body_999', $e->getRequestId());
            $this->assertSame('Invalid API key credentials provided.', $e->getMessage());
        }
    }

    public function testMaps403ToPermissionException(): void
    {
        $mockResponse = new Response(
            403,
            ['Content-Type' => 'application/json', 'X-Request-Id' => 'req_perm_123'],
            (string) json_encode([
                'error' => [
                    'code' => 'INSUFFICIENT_SCOPE',
                    'message' => "API key lacks scope 'organizations:write'.",
                ],
            ])
        );

        $nexora = $this->createMockClient([$mockResponse]);

        $this->expectException(PermissionException::class);
        $this->expectExceptionMessage("API key lacks scope 'organizations:write'.");

        $nexora->organizations()->create(['name' => 'Fail Org', 'type' => 'HOSPITAL']);
    }

    public function testMaps400ToValidationException(): void
    {
        $mockResponse = new Response(
            400,
            ['Content-Type' => 'application/json'],
            (string) json_encode([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'firstName and lastName are required.',
                ],
            ])
        );

        $nexora = $this->createMockClient([$mockResponse]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('firstName and lastName are required.');

        $nexora->users()->create('org_1', []);
    }

    public function testMaps429ToRateLimitExceptionWithRetryAfter(): void
    {
        $mockResponse = new Response(
            429,
            ['Content-Type' => 'application/json', 'Retry-After' => '45'],
            (string) json_encode([
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Rate limit exceeded. Please throttle requests.',
                ],
            ])
        );

        $nexora = $this->createMockClient([$mockResponse]);

        try {
            $nexora->organizations()->list();
            $this->fail('Expected RateLimitException was not thrown');
        } catch (RateLimitException $e) {
            $this->assertSame(429, $e->getStatusCode());
            $this->assertSame('RATE_LIMIT_EXCEEDED', $e->getErrorCode());
            $this->assertSame(45, $e->getRetryAfter());
        }
    }

    public function testMaps500ToNexoraException(): void
    {
        $mockResponse = new Response(
            500,
            ['Content-Type' => 'application/json'],
            (string) json_encode([
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'A fatal server error occurred.',
                ],
            ])
        );

        $nexora = $this->createMockClient([$mockResponse]);

        $this->expectException(NexoraException::class);
        $this->expectExceptionMessage('A fatal server error occurred.');

        $nexora->usage()->getProject();
    }

    public function testMapsConnectionFailureToNetworkException(): void
    {
        $connectException = new ConnectException(
            'cURL error 28: Connection timed out',
            new Request('GET', 'https://api.nexoragms.com/developer/v1/modules')
        );

        $nexora = $this->createMockClient([$connectException]);

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Network connection failed');

        $nexora->modules()->list();
    }
}
