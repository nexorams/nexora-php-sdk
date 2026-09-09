<?php

declare(strict_types=1);

namespace Nexora\Sdk\Tests\Unit;

use Nexora\Sdk\Exception\ValidationException;
use Nexora\Sdk\Nexora;
use Nexora\Sdk\Resources\Deliveries;
use Nexora\Sdk\Resources\Domains;
use Nexora\Sdk\Resources\Modules;
use Nexora\Sdk\Resources\Organizations;
use Nexora\Sdk\Resources\Plans;
use Nexora\Sdk\Resources\Subscriptions;
use Nexora\Sdk\Resources\Usage;
use Nexora\Sdk\Resources\Users;
use Nexora\Sdk\Resources\Webhooks;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    public function testMissingApiKeyThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('API key is required to initialize the Nexora SDK.');
        new Nexora('');
    }

    public function testWhitespaceApiKeyThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);
        new Nexora('   ');
    }

    public function testInvalidApiKeyPrefixThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Invalid API key prefix. Expected 'nx_test_' for sandbox or 'nx_live_' for live.");
        new Nexora('sk_invalid_prefix_12345');
    }

    public function testInfersSandboxEnvironmentFromTestKey(): void
    {
        $nexora = new Nexora('nx_test_sample_sandbox_key_123');
        $this->assertSame('sandbox', $nexora->getEnvironment());
        $this->assertSame('https://api.nexoragms.com/developer/v1', $nexora->getBaseUrl());
    }

    public function testInfersLiveEnvironmentFromLiveKey(): void
    {
        $nexora = new Nexora('nx_live_sample_live_key_456');
        $this->assertSame('live', $nexora->getEnvironment());
        $this->assertSame('https://api.nexoragms.com/developer/v1', $nexora->getBaseUrl());
    }

    public function testCustomBaseUrlNormalizesTrailingSlashes(): void
    {
        $nexora = new Nexora(
            apiKey: 'nx_test_sample_key',
            baseUrl: 'http://localhost:5000/developer/v1///'
        );
        $this->assertSame('http://localhost:5000/developer/v1', $nexora->getBaseUrl());
    }

    public function testExposesAllPlatformResourcesViaMethodsAndProperties(): void
    {
        $nexora = new Nexora('nx_test_sample_key');

        $this->assertInstanceOf(Organizations::class, $nexora->organizations());
        $this->assertInstanceOf(Organizations::class, $nexora->organizations);

        $this->assertInstanceOf(Users::class, $nexora->users());
        $this->assertInstanceOf(Users::class, $nexora->users);

        $this->assertInstanceOf(Modules::class, $nexora->modules());
        $this->assertInstanceOf(Modules::class, $nexora->modules);

        $this->assertInstanceOf(Plans::class, $nexora->plans());
        $this->assertInstanceOf(Plans::class, $nexora->plans);

        $this->assertInstanceOf(Subscriptions::class, $nexora->subscriptions());
        $this->assertInstanceOf(Subscriptions::class, $nexora->subscriptions);

        $this->assertInstanceOf(Domains::class, $nexora->domains());
        $this->assertInstanceOf(Domains::class, $nexora->domains);

        $this->assertInstanceOf(Webhooks::class, $nexora->webhooks());
        $this->assertInstanceOf(Webhooks::class, $nexora->webhooks);

        $this->assertInstanceOf(Deliveries::class, $nexora->deliveries());
        $this->assertInstanceOf(Deliveries::class, $nexora->deliveries);

        $this->assertInstanceOf(Usage::class, $nexora->usage());
        $this->assertInstanceOf(Usage::class, $nexora->usage);
    }
}
