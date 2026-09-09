<?php

declare(strict_types=1);

namespace Nexora\Sdk\Tests\Unit;

use Nexora\Sdk\Exception\NexoraException;
use Nexora\Sdk\Nexora;
use PHPUnit\Framework\TestCase;

final class SecurityTest extends TestCase
{
    public function testDebugInfoMasksApiKey(): void
    {
        $secretEntropy = 'SUPER_SECRET_VALUE_99999999';
        $rawKey = 'nx_test_' . $secretEntropy;

        $nexora = new Nexora($rawKey);
        $debug = $nexora->__debugInfo();

        $this->assertArrayHasKey('apiKey', $debug);
        $this->assertStringNotContainsString($secretEntropy, (string) $debug['apiKey']);
        $this->assertStringContainsString('••••••••', (string) $debug['apiKey']);

        // Test output buffer of var_dump
        ob_start();
        var_dump($nexora);
        $dump = ob_get_clean();

        $this->assertIsString($dump);
        $this->assertStringNotContainsString($secretEntropy, $dump);
    }

    public function testExceptionSanitizesApiKeyFromMessageAndOutput(): void
    {
        $secretEntropy = 'SECRET_TOKEN_EMBEDDED_9999';
        $rawKey = 'nx_test_' . $secretEntropy;

        $e = new NexoraException(
            message: 'Failed to authenticate with key ' . $rawKey,
            errorCode: 'AUTH_FAILED',
            statusCode: 401,
            requestId: 'req_test_123'
        );

        $this->assertStringNotContainsString($secretEntropy, $e->getMessage());
        $this->assertStringNotContainsString($secretEntropy, (string) $e);
        $this->assertStringNotContainsString($secretEntropy, json_encode($e->toArray(), JSON_THROW_ON_ERROR));
    }

    public function testSerializedClientDoesNotContainRawApiKey(): void
    {
        $secretEntropy = 'UNSERIALIZABLE_SECRET_VALUE_123';
        $rawKey = 'nx_test_' . $secretEntropy;

        $nexora = new Nexora($rawKey);
        $serialized = serialize($nexora);

        $this->assertStringNotContainsString($secretEntropy, $serialized);
    }
}
