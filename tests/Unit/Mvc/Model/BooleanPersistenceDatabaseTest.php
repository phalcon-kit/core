<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Model;

use Phalcon\Di\Di;
use Phalcon\Mvc\Model\MetaData\Memory;
use PhalconKit\Config\Config;
use PhalconKit\Mvc\Model;
use PhalconKit\Mvc\Model\Manager;
use PhalconKit\Support\Helper;
use PhalconKit\Support\HelperFactory;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\BooleanPersistenceModel;
use PhalconKit\Tests\Unit\Support\Fixtures\DatabaseTestCase;

/** Opt-in test using an isolated disposable MariaDB or CI MySQL server. */
final class BooleanPersistenceDatabaseTest extends DatabaseTestCase
{
    public function testBooleanCreateUpdateAndReloadWithRealMetadata(): void
    {
        $db = $this->connectTestDatabase('PHALCONKIT_RELATION_TEST_SOCKET', 'PHALCONKIT_BOOLEAN_TEST_HOST');
        $previousDi = Di::getDefault();
        $previousHelper = Helper::$helperFactory;
        $database = 'phalconkit_boolean_' . bin2hex(random_bytes(8));
        $db->execute('CREATE DATABASE `' . $database . '`');
        try {
            $db->execute('USE `' . $database . '`');
            $db->execute('CREATE TABLE boolean_flags (id INT AUTO_INCREMENT PRIMARY KEY, is_enabled TINYINT UNSIGNED NOT NULL, optional_flag TINYINT UNSIGNED NULL, quantity INT UNSIGNED NOT NULL)');
            $di = new \PhalconKit\Di\Di();
            $di->setShared('config', new Config());
            $di->setShared('helper', new HelperFactory());
            $di->setShared('filter', (new \Phalcon\Filter\FilterFactory())->newInstance());
            $di->setShared('translate', new \Phalcon\Translate\Adapter\NativeArray(
                new \Phalcon\Translate\InterpolatorFactory(),
                ['content' => []]
            ));
            $di->setShared('db', $db);
            $di->setShared('modelsManager', new Manager());
            $di->setShared('modelsMetadata', new Memory());
            Di::setDefault($di);
            Helper::$helperFactory = null;
            Model::setup();

            foreach ([true, false, 1, 0, '1', '0'] as $value) {
                $model = new BooleanPersistenceModel();
                $model->assign(['enabled' => $value, 'optionalFlag' => null]);
                self::assertTrue($model->save(), json_encode($model->getMessages()));
                self::assertSame((int)$value, $model->enabled);
                self::assertSame((int)$value, $model->toArray()['enabled']);
                $fresh = BooleanPersistenceModel::findFirst($model->id);
                self::assertSame((int)$value, $fresh->enabled);
                self::assertNull($fresh->optionalFlag);
                self::assertSame(42, $fresh->quantity);

                $fresh->assign(['enabled' => !$value, 'optionalFlag' => false]);
                self::assertTrue($fresh->save());
                self::assertSame((int)!$value, $fresh->enabled);
                self::assertSame(0, $fresh->optionalFlag);
                $updated = BooleanPersistenceModel::findFirst($model->id);
                self::assertSame((int)!$value, $updated->enabled);
                self::assertSame(0, $updated->optionalFlag);

                // Invalid input cannot overwrite a previously saved flag.
                $updated->assign(['enabled' => 'false']);
                self::assertFalse($updated->save());
                self::assertNotEmpty($updated->getMessages());
                self::assertSame((int)!$value, BooleanPersistenceModel::findFirst($model->id)->enabled);

                $updated->assign(['enabled' => $value, 'optionalFlag' => '']);
                self::assertTrue($updated->save());
                self::assertNull($updated->optionalFlag);
                self::assertNull(BooleanPersistenceModel::findFirst($model->id)->optionalFlag);
            }
        } finally {
            if ($db->isUnderTransaction()) {
                $db->rollback();
            }
            $db->execute('DROP DATABASE `' . $database . '`');
            $db->close();
            Helper::$helperFactory = $previousHelper;
            Di::reset();
            if ($previousDi !== null) {
                Di::setDefault($previousDi);
            }
        }
    }
}
