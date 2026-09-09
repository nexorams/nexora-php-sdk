<?php

declare(strict_types=1);

namespace Nexora\Sdk\Tests\Integration;

use Nexora\Sdk\Nexora;
use PHPUnit\Framework\TestCase;

final class IntegrationTest extends TestCase
{
    public function testSandboxLiveApiIntegrationWhenConfigured(): void
    {
        if (getenv('NEXORA_RUN_INTEGRATION_TESTS') !== '1') {
            $this->markTestSkipped('Integration tests disabled. Set NEXORA_RUN_INTEGRATION_TESTS=1 to run.');
        }

        $apiKey = getenv('NEXORA_API_KEY') ?: '';
        if ($apiKey === '') {
            $this->markTestSkipped('NEXORA_API_KEY not provided for integration testing.');
        }

        // Safety Guard: never run destructive integration tests with LIVE credentials
        if (str_starts_with($apiKey, 'nx_live_')) {
            $this->markTestSkipped('LIVE API key detected. Integration tests require a TEST (nx_test_...) key.');
        }

        $baseUrl = getenv('NEXORA_API_BASE_URL') ?: 'https://api.nexoragms.com/developer/v1';

        $nexora = new Nexora(
            apiKey: $apiKey,
            baseUrl: $baseUrl
        );

        $modules = $nexora->modules()->list();
        $this->assertIsArray($modules);

        $plans = $nexora->plans()->list();
        $this->assertIsArray($plans);
    }
}
