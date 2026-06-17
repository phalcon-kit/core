<?php

/**
 * Minimal Psalm stub for the optional ext-redis classes used by providers.
 *
 * Runtime Redis support still requires the PHP extension. This file only lets
 * static analysis run in development environments where the extension is not
 * loaded.
 */
class Redis
{
    public function __construct(?array $options = null)
    {
    }

    public function connect(
        string $host,
        int $port = 6379,
        float $timeout = 0.0,
        ?string $persistentId = null,
        int $retryInterval = 0,
        float $readTimeout = 0.0,
        mixed $context = null
    ): bool {
    }

    public function auth(mixed $credentials): bool
    {
    }

    public function select(int $database): bool
    {
    }
}

class RedisException extends Exception
{
}
