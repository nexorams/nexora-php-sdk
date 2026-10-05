<?php

declare(strict_types=1);

namespace Nexora\Sdk\Tests\Unit;

use Nexora\Sdk\Support\Version;
use PHPUnit\Framework\TestCase;

final class VersionTest extends TestCase
{
    public function testUserAgentCarriesTheSdkVersion(): void
    {
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', Version::VERSION);
        $this->assertSame('nexorams-php/' . Version::VERSION, Version::USER_AGENT);
    }

    public function testVersionIsNotOlderThanTheLatestReleaseTag(): void
    {
        $tags = @shell_exec('git -C ' . escapeshellarg(dirname(__DIR__, 2)) . ' tag --list "v*" 2>' . (PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null'));
        $versions = array_filter(array_map(static fn (string $t): string => ltrim(trim($t), 'v'), explode("\n", (string) $tags)));
        if ($versions === []) {
            $this->markTestSkipped('No git release tags available.');
        }
        usort($versions, 'version_compare');
        $this->assertGreaterThanOrEqual(0, version_compare(Version::VERSION, end($versions)), 'Version::VERSION is older than the latest release tag');
    }
}
