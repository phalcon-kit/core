<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Identity;

use Phalcon\Db\Adapter\AdapterInterface;
use Phalcon\Di\Di;
use Phalcon\Filter\FilterFactory;
use Phalcon\Mvc\Model\MetaDataInterface;
use PhalconKit\Config\Config;
use PhalconKit\Encryption\Security;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Identity\Manager;
use PhalconKit\Models\Interfaces\UserInterface;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\HashModelDouble;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Native hash helpers and real reset consumer; only user storage/transaction IO is isolated. */
final class PasswordResetSecurityTest extends TestCase
{
    private ?\Phalcon\Di\DiInterface $previousDi;
    private Manager $identity;
    private object $state;
    private HashModelDouble $hasher;

    protected function setUp(): void
    {
        $this->previousDi = Di::getDefault();
        $di = new \PhalconKit\Di\Di();
        $di->setShared('config', new Config([
            'security' => ['salt' => 'synthetic-configured-salt', 'workFactor' => 4],
            'identity' => ['resetPassword' => ['lifetime' => 1800]],
        ]));
        $di->setShared('filter', new FilterFactory()->newInstance());
        $security = new Security();
        $security->setDI($di);
        $security->setDefaultHash(Security::CRYPT_BCRYPT);
        $di->setShared('security', $security);
        $this->hasher = new HashModelDouble($di);
        $this->state = (object)[
            'record' => null, 'password' => $this->hasher->hash('original password'),
            'dbRecord' => null, 'dbPassword' => null, 'snapshot' => null,
            'affected' => 0, 'transaction' => false, 'saves' => 0,
            'failSave' => false, 'throwSave' => false, 'failCommit' => false,
            'deleted' => false, 'exists' => true, 'claims' => 0,
        ];
        $this->state->dbPassword = $this->state->password;
        $state = $this->state;
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('isUnderTransaction')->willReturnCallback(static fn (): bool => $state->transaction);
        $connection->method('begin')->willReturnCallback(static function () use ($state): bool {
            $state->snapshot = [$state->dbRecord, $state->dbPassword];
            $state->transaction = true;
            return true;
        });
        $connection->method('escapeIdentifier')->willReturnCallback(static fn (string $name): string => '`' . $name . '`');
        $connection->method('execute')->willReturnCallback(function (string $sql, array $bind, array $types) use ($state): bool {
            self::assertSame('UPDATE `isolated`.`users` SET `reset_token` = NULL WHERE `id` = ? AND `reset_token` = ?', $sql);
            self::assertSame(42, $bind[0]);
            self::assertCount(2, $types);
            self::assertTrue($state->transaction);
            ++$state->claims;
            $state->affected = $state->dbRecord === $bind[1] ? 1 : 0;
            if ($state->affected === 1) {
                $state->dbRecord = null;
            }
            return true;
        });
        $connection->method('affectedRows')->willReturnCallback(static fn (): int => $state->affected);
        $connection->method('commit')->willReturnCallback(static function () use ($state): bool {
            if ($state->failCommit) {
                return false;
            }
            $state->transaction = false;
            return true;
        });
        $connection->method('rollback')->willReturnCallback(static function () use ($state): bool {
            [$state->dbRecord, $state->dbPassword] = $state->snapshot;
            $state->transaction = false;
            return true;
        });
        $metadata = $this->createStub(MetaDataInterface::class);
        $metadata->method('getColumnMap')->willReturn(['id' => 'id', 'reset_token' => 'resetToken', 'password' => 'password']);
        $user = $this->createStub(UserInterface::class);
        $user->method('getId')->willReturn(42);
        $user->method('getEmail')->willReturn('synthetic@example.test');
        $user->method('isDeleted')->willReturnCallback(static fn (): bool => $state->deleted);
        $user->method('getPassword')->willReturnCallback(static fn (): ?string => $state->password);
        $user->method('setPassword')->willReturnCallback(static function ($password) use ($state): void {
            $state->password = $password;
        });
        $user->method('getResetToken')->willReturnCallback(static fn (): ?string => $state->record);
        $user->method('setResetToken')->willReturnCallback(static function ($record) use ($state): void {
            $state->record = $record;
        });
        $user->method('hash')->willReturnCallback($this->hasher->hash(...));
        $user->method('checkHash')->willReturnCallback($this->hasher->checkHash(...));
        $user->method('getWriteConnection')->willReturn($connection);
        $user->method('getModelsMetaData')->willReturn($metadata);
        $user->method('getSource')->willReturn('users');
        $user->method('getSchema')->willReturn('isolated');
        $user->method('getMessages')->willReturn([]);
        $user->method('save')->willReturnCallback(static function () use ($state): bool {
            ++$state->saves;
            if ($state->throwSave) {
                throw new \RuntimeException('Synthetic save failure');
            }
            if ($state->failSave) {
                return false;
            }
            $state->dbRecord = $state->record;
            $state->dbPassword = $state->password;
            return true;
        });
        $this->identity = new class extends Manager {
            public ?UserInterface $testUser = null;
            public ?object $storage = null;
            public array $delivery = [];

            public function findUserByEmail(string $string): ?UserInterface
            {
                return $this->storage->exists ? $this->testUser : null;
            }

            protected function sendPasswordResetNotification(UserInterface $user, string $token, int $expiresAt): void
            {
                $this->delivery = [$token, $expiresAt];
            }
        };
        $this->identity->testUser = $user;
        $this->identity->storage = $state;
        $this->identity->setDI($di);
    }

    protected function tearDown(): void
    {
        Di::reset();
        if ($this->previousDi !== null) {
            Di::setDefault($this->previousDi);
        }
    }

    public function testRequestAndRedemptionUseRealHashingAndConsumeOnce(): void
    {
        $token = $this->issue();
        $record = $this->state->record;
        $result = $this->redeem($token);
        self::assertSame([], $result);
        self::assertNull($this->state->dbRecord);
        self::assertNotSame('replacement password', $this->state->dbPassword);
        self::assertTrue($this->hasher->checkHash($this->state->dbPassword, 'replacement password'));
        self::assertFalse($this->state->transaction);
        self::assertSame(1, $this->state->claims);

        // A competing request loaded the old user before the first redemption.
        $this->state->record = $record;
        $saves = $this->state->saves;
        $this->assertInvalid($this->redeem($token));
        self::assertSame($saves, $this->state->saves);
        self::assertNull($this->state->dbRecord);
    }

    public static function invalidRecords(): array
    {
        return array_map(static fn (string $case): array => [$case], ['expired', 'legacy', 'malformed', 'wrong', 'deleted', 'unknown']);
    }

    #[DataProvider('invalidRecords')]
    public function testInvalidRedemptionDoesNotChangeCredentials(string $case): void
    {
        $token = $this->issue();
        switch ($case) {
            case 'expired':
                $this->state->record = 'v1:' . (time() - 1) . ':' . explode(':', $this->state->record, 3)[2];
                break;
            case 'legacy':
                $this->state->record = $this->hasher->hash($token);
                break;
            case 'malformed':
                $this->state->record = 'v1:not-a-date:anything';
                break;
            case 'wrong':
                $token = 'synthetic-wrong-token';
                break;
            case 'deleted':
                $this->state->deleted = true;
                break;
            case 'unknown':
                $this->state->exists = false;
                break;
        }
        $before = [$this->state->dbRecord, $this->state->dbPassword, $this->state->saves];
        $this->assertInvalid($this->redeem($token));
        self::assertSame($before, [$this->state->dbRecord, $this->state->dbPassword, $this->state->saves]);
        self::assertSame(0, $this->state->claims);
    }

    public static function failedWrites(): array
    {
        return [['failSave'], ['throwSave'], ['failCommit']];
    }

    #[DataProvider('failedWrites')]
    public function testFailedPersistenceRestoresDatabaseAndModel(string $failure): void
    {
        $token = $this->issue();
        $before = [$this->state->record, $this->state->password];
        $this->state->$failure = true;
        try {
            $result = $this->redeem($token);
            self::assertSame('failSave', $failure);
            $this->assertInvalid($result);
        }
        catch (\RuntimeException $exception) {
            self::assertNotSame('failSave', $failure);
        }
        self::assertSame($before, [$this->state->record, $this->state->password]);
        self::assertSame($before, [$this->state->dbRecord, $this->state->dbPassword]);
        self::assertFalse($this->state->transaction);
    }

    public function testExpiryIsRecheckedWhenClaimingAPreviouslyValidatedRecord(): void
    {
        $this->issue();
        $record = 'v1:' . (time() - 1) . ':' . explode(':', $this->state->record, 3)[2];
        $this->state->record = $record;
        $this->state->dbRecord = $record;
        $beforePassword = $this->state->password;
        $method = new \ReflectionMethod($this->identity, 'persistPasswordReset');
        self::assertFalse($method->invoke($this->identity, $this->identity->testUser, $record, 'replacement password'));
        self::assertSame($record, $this->state->dbRecord);
        self::assertSame($beforePassword, $this->state->dbPassword);
        self::assertSame(1, $this->state->saves);
        self::assertFalse($this->state->transaction);
    }

    public function testOuterTransactionIsNotCommittedOrRolledBack(): void
    {
        $token = $this->issue();
        $this->state->transaction = true;
        try {
            $this->redeem($token);
            self::fail('Expected transaction ownership rejection.');
        }
        catch (ServiceException $exception) {
            self::assertTrue($this->state->transaction);
            self::assertSame(0, $this->state->claims);
        }
    }

    public function testNewRequestInvalidatesPreviousTokenAndKeepsTokenOutOfResponse(): void
    {
        $first = $this->issue();
        $second = $this->issue();
        self::assertNotSame($first, $second);
        $this->assertInvalid($this->redeem($first));
        self::assertSame([], $this->redeem($second));
    }

    private function issue(): string
    {
        self::assertSame([], $this->identity->reset(['email' => 'synthetic@example.test']));
        [$token, $expiry] = $this->identity->delivery;
        self::assertGreaterThan(time(), $expiry);
        self::assertStringNotContainsString($token, $this->state->record);
        self::assertLessThanOrEqual(255, strlen($this->state->record));
        return $token;
    }

    private function redeem(string $token): array
    {
        return $this->identity->reset([
            'email' => 'synthetic@example.test', 'resetToken' => $token, 'password' => 'replacement password',
        ]);
    }

    private function assertInvalid(array $result): void
    {
        self::assertSame(400, $result['messages'][0]->getCode());
        self::assertSame('Invalid or expired reset token', $result['messages'][0]->getMessage());
    }
}
