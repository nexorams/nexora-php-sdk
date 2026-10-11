<?php

declare(strict_types=1);

namespace Nexora\Sdk\Tests\Unit;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Nexora\Sdk\Exception\PermissionException;
use Nexora\Sdk\Exception\ValidationException;
use Nexora\Sdk\Nexora;
use PHPUnit\Framework\TestCase;

final class CloudTest extends TestCase
{
    private const APP = 'aaaaaaaaaaaaaaaaaaaaaaaa';
    private array $history = [];

    private function client(array $responses, ?string $organizationId = 'org_1', ?string $applicationId = self::APP): Nexora
    {
        $this->history = [];
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        return new Nexora(
            apiKey: 'nx_live_mock_secret_key_123',
            baseUrl: 'https://api.nexoragms.com/developer/v1',
            httpClient: new GuzzleClient(['handler' => $stack]),
            organizationId: $organizationId,
            applicationId: $applicationId
        );
    }

    private function json(array $payload, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], (string) json_encode($payload));
    }

    private function last()
    {
        return end($this->history)['request'];
    }

    public function testEntitiesAreResolvedDynamically(): void
    {
        $nexora = $this->client([]);
        $this->assertSame('members', $nexora->resources('members')->entityKey);
        $this->assertSame(self::APP, $nexora->resources('members')->applicationId);
        $this->assertSame('trainers', $nexora->cloud()->app(self::APP)->entity('trainers')->entityKey);
        $this->assertSame(
            ['created' => 'cloud.members.created', 'updated' => 'cloud.members.updated', 'archived' => 'cloud.members.archived'],
            $nexora->resources('members')->events()
        );
        $this->assertSame($nexora->cloud(), $nexora->cloud);
    }

    public function testCrudUsesGenericEndpointsHeadersAndIdempotency(): void
    {
        $nexora = $this->client([
            $this->json(['data' => [['id' => 'r1']], 'pagination' => ['page' => 1, 'totalPages' => 2, 'total' => 2]]),
            $this->json(['data' => [['id' => 'r1']], 'pagination' => ['page' => 1, 'totalPages' => 2, 'total' => 2]]),
            $this->json(['data' => [['id' => 'r2']], 'pagination' => ['page' => 2, 'totalPages' => 2, 'total' => 2]]),
            $this->json(['data' => ['id' => 'new', 'data' => ['first_name' => 'Ada']]], 201),
            $this->json(['data' => ['id' => 'r1', 'data' => ['plan' => 'gold']]]),
            $this->json(['data' => ['id' => 'r1', 'status' => 'ARCHIVED']]),
            $this->json(['data' => ['id' => 'r1']]),
        ]);

        $page = $nexora->resources('members')->list(['page' => 1, 'limit' => 1]);
        $this->assertSame(2, $page['pagination']['totalPages']);
        $req = $this->last();
        $this->assertSame('GET', $req->getMethod());
        $this->assertSame('/developer/v1/cloud/apps/' . self::APP . '/entities/members/records', $req->getUri()->getPath());
        $this->assertSame('org_1', $req->getHeaderLine('X-Organization-Id'));
        $this->assertSame('Bearer nx_live_mock_secret_key_123', $req->getHeaderLine('Authorization'));

        $ids = [];
        foreach ($nexora->resources('members')->listAll(1) as $record) {
            $ids[] = $record['id'];
        }
        $this->assertSame(['r1', 'r2'], $ids);

        $created = $nexora->resources('members')->create(['first_name' => 'Ada'], [], 'create-ada-1');
        $this->assertSame('new', $created['id']);
        $this->assertSame('create-ada-1', $this->last()->getHeaderLine('Idempotency-Key'));
        $this->assertSame(['data' => ['first_name' => 'Ada']], json_decode((string) $this->last()->getBody(), true));

        $this->assertSame('gold', $nexora->resources('members')->update('r1', ['plan' => 'gold'])['data']['plan']);
        $this->assertSame('PATCH', $this->last()->getMethod());
        $this->assertSame('ARCHIVED', $nexora->resources('members')->archive('r1')['status']);
        $this->assertSame('DELETE', $this->last()->getMethod());
        $this->assertSame('r1', $nexora->resources('members')->get('r1')['id']);
    }

    public function testPerCallOrganizationOverrideAndDiscovery(): void
    {
        $nexora = $this->client([
            $this->json(['data' => [['organizationId' => 'o1', 'applicationId' => self::APP, 'sandbox' => false]]]),
            $this->json(['data' => ['applicationId' => self::APP, 'entities' => [['key' => 'members']]]]),
            $this->json(['data' => [], 'pagination' => ['page' => 1, 'totalPages' => 1, 'total' => 0]]),
        ]);

        $this->assertSame('o1', $nexora->cloud()->installations()[0]['organizationId']);
        $this->assertSame('members', $nexora->cloud()->schema()['entities'][0]['key']);
        $nexora->resources('members')->list([], ['X-Organization-Id' => 'org_2']);
        $this->assertSame('org_2', $this->last()->getHeaderLine('X-Organization-Id'));
    }

    public function testErrorsAreMapped(): void
    {
        $nexora = $this->client([$this->json(['error' => ['code' => 'PERMISSION_DENIED', 'message' => 'Missing Cloud permission', 'requestId' => 'req_1']], 403)]);
        try {
            $nexora->resources('forbidden')->list();
            $this->fail('expected a PermissionException');
        } catch (PermissionException $e) {
            $this->assertSame('PERMISSION_DENIED', $e->getErrorCode());
        }
    }

    public function testInputIsValidatedBeforeAnyRequest(): void
    {
        $nexora = $this->client([]);
        foreach ([fn () => $nexora->resources('Not-Valid'), fn () => $nexora->resources('members', 'short')] as $call) {
            try {
                $call();
                $this->fail('expected a ValidationException');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
        $bare = $this->client([], 'org_1', null);
        $this->expectException(ValidationException::class);
        $bare->resources('members');
    }
}
