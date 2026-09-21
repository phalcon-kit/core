<?php

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Support;

use Phalcon\Db\Exceptions\NoActiveTransaction;
use Phalcon\Filter\Validation\Validator\File\Size\Max;
use Phalcon\Filter\Validation\Validator\Ip;
use Phalcon\Forms\Element\CheckGroup;
use Phalcon\Forms\Element\RadioGroup;
use Phalcon\Forms\Element\Select;
use PhalconKit\Db\Adapter\Pdo\Mysql;
use PhalconKit\Filter\Validation;
use PhalconKit\Tests\Unit\AbstractUnit;
use PHPUnit\Framework\Attributes\CoversNothing;

/** Regression coverage for native behavior inherited by Phalcon Kit. */
#[CoversNothing]
final class Phalcon521CompatibilityTest extends AbstractUnit
{
    public function testInactiveTransactionsThrowWithoutChangingNesting(): void
    {
        $connection = $this->transactionConnection();
        $this->assertFalse($connection->isUnderTransaction());

        foreach (['commit', 'rollback'] as $operation) {
            try {
                $connection->{$operation}();
                $this->fail($operation . ' must reject an inactive transaction.');
            } catch (NoActiveTransaction) {
                $this->assertSame(0, $connection->getTransactionLevel());
            }
        }

        $this->assertTrue($connection->begin());
        try {
            $this->assertSame(1, $connection->getTransactionLevel());
            // ORM relationship persistence uses logical nesting without savepoints.
            $this->assertFalse($connection->begin(false));
            $this->assertSame(2, $connection->getTransactionLevel());
            $this->assertFalse($connection->commit(false));
            $this->assertSame(1, $connection->getTransactionLevel());
            $this->assertTrue($connection->isUnderTransaction());
        } finally {
            $connection->rollback();
        }
        $this->assertSame(0, $connection->getTransactionLevel());
    }

    public function testFailedBeginPreservesTransactionLevel(): void
    {
        $connection = $this->transactionConnection();
        $handler = $connection->getInternalHandler();
        $this->assertInstanceOf(\PDO::class, $handler);
        $handler->beginTransaction();
        try {
            $failed = false;
            try {
                $connection->begin();
            } catch (\PDOException) {
                $failed = true;
            }
            $this->assertTrue($failed, 'PDO must reject a second physical transaction.');
            $this->assertSame(0, $connection->getTransactionLevel());
            $this->assertTrue($handler->inTransaction());
        } finally {
            $handler->rollBack();
        }
    }

    public function testFileValidationRejectsMissingAndNonUploadValues(): void
    {
        foreach ([[], ['file' => null], ['file' => 'not-an-upload']] as $data) {
            $validation = new Validation();
            $validation->add('file', new Max(['maxSize' => '1M']));
            $this->assertCount(1, $validation->validate($data));
        }
    }

    public function testIpOptionsAreResolvedForEachField(): void
    {
        $validation = new Validation();
        $validation->add(['internal', 'external'], new Ip([
            'allowPrivate' => ['internal' => true, 'external' => false],
            'allowReserved' => ['internal' => true, 'external' => false],
        ]));

        foreach (['10.0.0.1', '127.0.0.1'] as $address) {
            $messages = $validation->validate(['internal' => $address, 'external' => $address]);
            $this->assertCount(1, $messages);
            $this->assertSame('external', $messages->current()->getField());
        }
    }

    public function testChoiceOptionsDoNotOverwriteUserOptions(): void
    {
        foreach ([new CheckGroup('choice', ['a' => 'A']), new RadioGroup('choice', ['a' => 'A'])] as $element) {
            $element->setUserOption('hint', 'Choose one');
            $this->assertSame(['a' => 'A'], $element->getOptions());
            $element->setOptions(['b' => 'B']);
            $this->assertSame('Choose one', $element->getUserOption('hint'));
            $this->assertSame(['b' => 'B'], $element->getOptions());
        }

        $select = new Select('choice');
        $select->addOption(['a' => 'A']);
        $this->assertSame(['a' => 'A'], $select->getOptions());
    }

    private function transactionConnection(): \Phalcon\Db\Adapter\Pdo\Mysql
    {
        $socket = getenv('PHALCONKIT_RELATION_TEST_SOCKET');
        return $socket
            ? new Mysql(['unix_socket' => $socket, 'username' => 'root', 'password' => ''])
            : $this->getDb();
    }
}
