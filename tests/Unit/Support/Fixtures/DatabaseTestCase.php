<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Support\Fixtures;

use Phalcon\Db\Adapter\Pdo\Mysql;
use PHPUnit\Framework\TestCase;

/** Explicit opt-in connection to a disposable MySQL/MariaDB test server. */
abstract class DatabaseTestCase extends TestCase
{
    /**
     * Tests create and drop their own random schemas on this server.
     * The legacy variables keep existing local test commands working.
     */
    protected function connectTestDatabase(?string $legacySocket = null, ?string $legacyHost = null): Mysql
    {
        $socket = getenv('PHALCONKIT_TEST_DB_SOCKET') ?: ($legacySocket ? getenv($legacySocket) : false);
        $host = getenv('PHALCONKIT_TEST_DB_HOST') ?: ($legacyHost ? getenv($legacyHost) : false);
        if (!$socket && !$host) {
            self::markTestSkipped('Set PHALCONKIT_TEST_DB_SOCKET or PHALCONKIT_TEST_DB_HOST to a disposable database server.');
        }

        return new Mysql(($socket ? ['unix_socket' => $socket] : ['host' => $host, 'port' => (int) (getenv('PHALCONKIT_TEST_DB_PORT') ?: 3306)]) + [
            'username' => 'root',
            'password' => '',
        ]);
    }
}
