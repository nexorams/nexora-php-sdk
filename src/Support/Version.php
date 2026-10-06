<?php

declare(strict_types=1);

namespace Nexora\Sdk\Support;

final class Version
{
    // The released package version (git tag). Sent in the User-Agent and shown in
    // Developer Portal request logs; update it with every release.
    public const VERSION = '2.0.0';
    public const USER_AGENT = 'nexorams-php/' . self::VERSION;
}
