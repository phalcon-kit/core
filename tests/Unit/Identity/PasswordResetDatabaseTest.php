<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Identity;

use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Di\Di;
use Phalcon\Encryption\Security;
use PhalconKit\Config\Config;
use PhalconKit\Identity\Manager;
use PhalconKit\Models\Interfaces\UserInterface;
use PhalconKit\Tests\Unit\Identity\Fixtures\PasswordResetUser;
use PHPUnit\Framework\TestCase;

/** Opt-in test against a disposable, socket-only MariaDB/MySQL instance. */
final class PasswordResetDatabaseTest extends TestCase
{
    public function testNativeOrmCommitsOnceAndRollsBackRejectedPassword(): void
    {
        $socket = getenv('PHALCONKIT_RESET_TEST_SOCKET');
        if (!$socket) {
            self::markTestSkipped('Set PHALCONKIT_RESET_TEST_SOCKET to an isolated disposable database socket.');
        }
        $previousDi = Di::getDefault();
        $previousHelperFactory = \PhalconKit\Support\Helper::$helperFactory;
        $database = 'phalconkit_reset_' . bin2hex(random_bytes(8));
        $connection = new Mysql(['unix_socket' => $socket, 'username' => 'root', 'password' => '']);
        $connection->execute('CREATE DATABASE `' . $database . '`');
        try {
            $connection->execute('USE `' . $database . '`');
            $connection->execute('CREATE TABLE reset_users (id INT PRIMARY KEY, email VARCHAR(255) NOT NULL, password VARCHAR(255), reset_token VARCHAR(255), deleted TINYINT NOT NULL DEFAULT 0) ENGINE=InnoDB');
            $di = new \PhalconKit\Di\Di();
            Di::setDefault($di);
            \PhalconKit\Support\Helper::$helperFactory = null;
            $di->setShared('config', new Config(['security' => ['salt' => 'synthetic-salt', 'workFactor' => 4]]));
            $di->setShared('db', $connection);
            $di->setShared('modelsManager', new \PhalconKit\Mvc\Model\Manager());
            $di->setShared('modelsMetadata', new \Phalcon\Mvc\Model\MetaData\Memory());
            $di->setShared('filter', (new \Phalcon\Filter\FilterFactory())->newInstance());
            $di->setShared('helper', new \PhalconKit\Support\HelperFactory());
            $security = new \PhalconKit\Encryption\Security();
            $security->setDI($di);
            $security->setDefaultHash(Security::CRYPT_BCRYPT);
            $di->setShared('security', $security);
            $user = new PasswordResetUser();
            self::assertSame($connection, $user->getWriteConnection());
            $user->id = 42;
            $user->email = 'synthetic@example.test';
            $user->deleted = 0;
            $user->setPassword($user->hash('original password'));
            self::assertTrue($user->save());

            $identity = new class extends Manager {
                public ?UserInterface $testUser = null;
                public string $deliveredToken = '';

                public function findUserByEmail(string $string): ?UserInterface
                {
                    return $this->testUser;
                }

                protected function sendPasswordResetNotification(UserInterface $user, string $token, int $expiresAt): void
                {
                    $this->deliveredToken = $token;
                }
            };
            $identity->setDI($di);
            $identity->testUser = $user;
            self::assertSame([], $identity->reset(['email' => $user->email]));
            $stale = PasswordResetUser::findFirst(42);
            $request = ['email' => $user->email, 'resetToken' => $identity->deliveredToken, 'password' => 'replacement password'];
            self::assertSame([], $identity->reset($request));
            $persisted = PasswordResetUser::findFirst(42);
            self::assertNull($persisted->getResetToken());
            self::assertTrue($persisted->checkHash($persisted->getPassword(), $request['password']));
            self::assertFalse($connection->isUnderTransaction());

            // A separate model still holding the old, valid record loses the claim.
            $identity->testUser = $stale;
            self::assertNotEmpty($identity->reset($request)['messages']);
            self::assertSame($persisted->getPassword(), PasswordResetUser::findFirst(42)->getPassword());

            $identity->testUser = $persisted;
            self::assertSame([], $identity->reset(['email' => $persisted->email]));
            $record = $persisted->getResetToken();
            $password = $persisted->getPassword();
            $persisted->rejectSave = true;
            $request['resetToken'] = $identity->deliveredToken;
            $request['password'] = 'rejected password';
            self::assertNotEmpty($identity->reset($request)['messages']);
            $afterFailure = PasswordResetUser::findFirst(42);
            self::assertSame($record, $afterFailure->getResetToken());
            self::assertSame($password, $afterFailure->getPassword());
            self::assertSame($record, $persisted->getResetToken());
            self::assertSame($password, $persisted->getPassword());
            self::assertFalse($connection->isUnderTransaction());
        }
        finally {
            if ($connection->isUnderTransaction()) {
                $connection->rollback();
            }
            $connection->execute('DROP DATABASE `' . $database . '`');
            $connection->close();
            \PhalconKit\Support\Helper::$helperFactory = $previousHelperFactory;
            Di::reset();
            if ($previousDi !== null) {
                Di::setDefault($previousDi);
            }
        }
    }
}
