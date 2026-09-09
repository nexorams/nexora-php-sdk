<?php

declare(strict_types=1);

namespace Nexora\Sdk\Tests\Unit;

use Nexora\Sdk\Nexora;
use Nexora\Sdk\Webhooks\SignatureVerifier;
use PHPUnit\Framework\TestCase;

final class WebhookSignatureTest extends TestCase
{
    private string $secret = 'whsec_test_secret_key_abcdef123456';
    private string $payload;

    protected function setUp(): void
    {
        $this->payload = (string) json_encode([
            'id' => 'evt_123',
            'type' => 'organization.created',
            'data' => ['name' => 'Acme Academy'],
        ], JSON_THROW_ON_ERROR);
    }

    public function testVerifiesValidStandardHeader(): void
    {
        $now = time();
        $signedPayload = "{$now}.{$this->payload}";
        $sig = hash_hmac('sha256', $signedPayload, $this->secret);
        $header = "t={$now},v1={$sig}";

        $this->assertTrue(
            Nexora::verifyWebhookSignature($this->payload, $header, $this->secret, 300)
        );
        $this->assertTrue(
            SignatureVerifier::verify($this->payload, $header, $this->secret, 300)
        );
    }

    public function testVerifiesDirectV1HeaderFormat(): void
    {
        $sig = hash_hmac('sha256', $this->payload, $this->secret);
        $header = "v1={$sig}";

        $this->assertTrue(
            Nexora::verifyWebhookSignature($this->payload, $header, $this->secret, 300)
        );
    }

    public function testRejectsTamperedPayload(): void
    {
        $now = time();
        $signedPayload = "{$now}.{$this->payload}";
        $sig = hash_hmac('sha256', $signedPayload, $this->secret);
        $header = "t={$now},v1={$sig}";

        $tamperedPayload = json_encode(['id' => 'evt_999', 'type' => 'tampered'], JSON_THROW_ON_ERROR);

        $this->assertFalse(
            Nexora::verifyWebhookSignature($tamperedPayload, $header, $this->secret, 300)
        );
    }

    public function testRejectsStaleTimestampBeyondTolerance(): void
    {
        $staleTime = time() - 600; // 10 minutes ago
        $signedPayload = "{$staleTime}.{$this->payload}";
        $sig = hash_hmac('sha256', $signedPayload, $this->secret);
        $header = "t={$staleTime},v1={$sig}";

        $this->assertFalse(
            Nexora::verifyWebhookSignature($this->payload, $header, $this->secret, 300)
        );
    }

    public function testRejectsWrongSecret(): void
    {
        $now = time();
        $signedPayload = "{$now}.{$this->payload}";
        $sig = hash_hmac('sha256', $signedPayload, $this->secret);
        $header = "t={$now},v1={$sig}";

        $this->assertFalse(
            Nexora::verifyWebhookSignature($this->payload, $header, 'whsec_wrong_secret_123', 300)
        );
    }

    public function testRejectsEmptyInputsGracefully(): void
    {
        $this->assertFalse(Nexora::verifyWebhookSignature('', 'v1=123', $this->secret));
        $this->assertFalse(Nexora::verifyWebhookSignature($this->payload, '', $this->secret));
        $this->assertFalse(Nexora::verifyWebhookSignature($this->payload, 'v1=123', ''));
    }
}
