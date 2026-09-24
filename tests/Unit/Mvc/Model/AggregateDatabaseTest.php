<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Model;

use Phalcon\Di\Di;
use Phalcon\Mvc\Model\MetaData\Memory;
use Phalcon\Mvc\Model\ResultsetInterface;
use PhalconKit\Config\Config;
use PhalconKit\Mvc\Model\Manager;
use PhalconKit\Support\Helper;
use PhalconKit\Support\HelperFactory;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\AggregateModel;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\NativeAggregateModel;
use PhalconKit\Tests\Unit\Support\Fixtures\DatabaseTestCase;

/** Compare Core's aggregate wrappers with the installed extension and driver. */
final class AggregateDatabaseTest extends DatabaseTestCase
{
    public function testMinimumAndMaximumMatchNativeValuesAndTypes(): void
    {
        $db = $this->connectTestDatabase();
        $previousDi = Di::getDefault();
        $previousHelper = Helper::$helperFactory;
        $database = 'phalconkit_aggregate_' . bin2hex(random_bytes(8));
        $db->execute('CREATE DATABASE `' . $database . '`');
        try {
            $db->execute('USE `' . $database . '`');
            $db->execute('CREATE TABLE aggregate_values (id INT PRIMARY KEY, label VARCHAR(80) NOT NULL, created_at DATETIME NOT NULL, amount DECIMAL(8,2) NOT NULL, score DOUBLE NOT NULL, category INT NOT NULL) ENGINE=InnoDB');
            $db->execute("INSERT INTO aggregate_values VALUES (1, 'Alpha', '2026-09-24 10:00:00', 3.50, 1.25, 1), (2, 'Zebra', '2026-09-25 10:00:00', 9.75, 4.50, 1), (3, 'Beta', '2026-09-26 10:00:00', 5.00, 2.75, 2)");
            $di = new \PhalconKit\Di\Di();
            $di->setShared('db', $db);
            $di->setShared('config', new Config());
            $di->setShared('modelsManager', new Manager());
            $di->setShared('modelsMetadata', new Memory());
            $di->setShared('helper', new HelperFactory());
            $di->setShared('filter', (new \Phalcon\Filter\FilterFactory())->newInstance());
            Di::setDefault($di);
            Helper::$helperFactory = null;

            foreach (['minimum', 'maximum'] as $method) {
                foreach (['id', 'label', 'created_at', 'amount', 'score'] as $column) {
                    $options = ['column' => $column];
                    $native = NativeAggregateModel::$method($options);
                    self::assertNotNull($native);
                    self::assertSame($native, AggregateModel::$method($options), $method . '(' . $column . ')');

                    $empty = $options + ['conditions' => 'id < 0'];
                    self::assertNull(NativeAggregateModel::$method($empty));
                    self::assertNull(AggregateModel::$method($empty));

                    $grouped = $options + ['group' => 'category', 'order' => 'category'];
                    $nativeGroups = NativeAggregateModel::$method($grouped);
                    $coreGroups = AggregateModel::$method($grouped);
                    self::assertInstanceOf(ResultsetInterface::class, $nativeGroups);
                    self::assertInstanceOf(ResultsetInterface::class, $coreGroups);
                    self::assertCount(2, $coreGroups);
                    self::assertSame($nativeGroups->toArray(), $coreGroups->toArray());
                }
            }
        } finally {
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
