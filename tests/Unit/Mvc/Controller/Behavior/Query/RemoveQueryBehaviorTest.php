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

namespace PhalconKit\Tests\Unit\Mvc\Controller\Behavior\Query;

use Phalcon\Events\Event;
use Phalcon\Support\Collection;
use PhalconKit\Mvc\Controller\Behavior\Query\Conditions\RemoveDefaultFilterCondition;
use PhalconKit\Mvc\Controller\Behavior\Query\Conditions\RemoveDefaultIdentityCondition;
use PhalconKit\Mvc\Controller\Behavior\Query\Conditions\RemoveDefaultPermissionCondition;
use PhalconKit\Mvc\Controller\Behavior\Query\Conditions\RemoveDefaultSearchCondition;
use PhalconKit\Mvc\Controller\Behavior\Query\Conditions\RemoveDefaultSoftDeleteCondition;
use PhalconKit\Mvc\Controller\Behavior\Query\Conditions\RemoveDefaultSoftDeleteConditionWhileFiltering;
use PhalconKit\Mvc\Controller\Behavior\Query\Conditions\RemoveFilterConditions;
use PhalconKit\Mvc\Controller\Behavior\Query\Conditions\RemoveIdentityConditions;
use PhalconKit\Mvc\Controller\Behavior\Query\Conditions\RemovePermissionConditions;
use PhalconKit\Mvc\Controller\Behavior\Query\Conditions\RemoveSearchConditions;
use PhalconKit\Mvc\Controller\Behavior\Query\Conditions\RemoveSoftDeleteConditions;
use PhalconKit\Mvc\Controller\Behavior\Query\Conditions\RemoveSoftDeleteConditionsWhileFiltering;
use PhalconKit\Mvc\Controller\Behavior\Query\Fields\RemoveExposeFields;
use PhalconKit\Mvc\Controller\Behavior\Query\Fields\RemoveFilterFields;
use PhalconKit\Mvc\Controller\Behavior\Query\Fields\RemoveMapFields;
use PhalconKit\Mvc\Controller\Behavior\Query\Fields\RemoveSaveFields;
use PhalconKit\Mvc\Controller\Behavior\Query\Fields\RemoveSearchFields;
use PhalconKit\Mvc\Controller\Behavior\Query\RemoveBind;
use PhalconKit\Mvc\Controller\Behavior\Query\RemoveCacheConfig;
use PhalconKit\Mvc\Controller\Behavior\Query\RemoveColumn;
use PhalconKit\Mvc\Controller\Behavior\Query\RemoveConditions;
use PhalconKit\Mvc\Controller\Behavior\Query\RemoveDefaultLimit;
use PhalconKit\Mvc\Controller\Behavior\Query\RemoveDistinct;
use PhalconKit\Mvc\Controller\Behavior\Query\RemoveGroup;
use PhalconKit\Mvc\Controller\Behavior\Query\RemoveHaving;
use PhalconKit\Mvc\Controller\Behavior\Query\RemoveJoins;
use PhalconKit\Mvc\Controller\Behavior\Query\RemoveLimit;
use PhalconKit\Mvc\Controller\Behavior\Query\RemoveMaxLimit;
use PhalconKit\Mvc\Controller\Behavior\Query\RemoveOffset;
use PhalconKit\Mvc\Controller\Behavior\Query\RemoveWith;
use PhalconKit\Mvc\Controller\Restful;
use PhalconKit\Tests\Unit\AbstractUnit;

class RemoveQueryBehaviorTest extends AbstractUnit
{
    public function testTopLevelQueryBehaviorsClearTheirCollections(): void
    {
        $cases = [
            [new RemoveBind(), 'setBind', 'getBind', 'afterInitializeBind'],
            [new RemoveCacheConfig(), 'setCacheConfig', 'getCacheConfig', 'afterInitializeCacheConfig'],
            [new RemoveColumn(), 'setColumn', 'getColumn', 'afterInitializeColumn'],
            [new RemoveConditions(), 'setConditions', 'getConditions', 'afterInitializeConditions'],
            [new RemoveDistinct(), 'setDistinct', 'getDistinct', 'afterInitializeDistinct'],
            [new RemoveGroup(), 'setGroup', 'getGroup', 'afterInitializeGroup'],
            [new RemoveHaving(), 'setHaving', 'getHaving', 'afterInitializeHaving'],
            [new RemoveJoins(), 'setJoins', 'getJoins', 'afterInitializeJoins'],
            [new RemoveWith(), 'setWith', 'getWith', 'afterInitializeWith'],
        ];

        foreach ($cases as [$behavior, $setter, $getter, $method]) {
            $controller = $this->newController();
            $controller->{$setter}(new Collection(['default' => 'value']));

            $this->assertSame(['default' => 'value'], $controller->{$getter}()->toArray());

            $behavior->{$method}($this->newEvent($controller), $controller);

            $this->assertSame([], $controller->{$getter}()->toArray());
        }
    }

    public function testFieldBehaviorsClearTheirCollections(): void
    {
        $cases = [
            [new RemoveExposeFields(), 'setExposeFields', 'getExposeFields'],
            [new RemoveFilterFields(), 'setFilterFields', 'getFilterFields'],
            [new RemoveMapFields(), 'setMapFields', 'getMapFields'],
            [new RemoveSaveFields(), 'setSaveFields', 'getSaveFields'],
            [new RemoveSearchFields(), 'setSearchFields', 'getSearchFields'],
        ];

        foreach ($cases as [$behavior, $setter, $getter]) {
            $controller = $this->newController();
            $controller->{$setter}(new Collection(['field']));

            $this->assertSame(['field'], $controller->{$getter}()->toArray());

            $behavior->afterInitializeFields($this->newEvent($controller), $controller);

            $this->assertSame([], $controller->{$getter}()->toArray());
        }
    }

    public function testDefaultConditionBehaviorsRemoveOnlyDefaultCondition(): void
    {
        $cases = [
            [new RemoveDefaultFilterCondition(), 'setFilterConditions', 'getFilterConditions'],
            [new RemoveDefaultIdentityCondition(), 'setIdentityConditions', 'getIdentityConditions'],
            [new RemoveDefaultPermissionCondition(), 'setPermissionConditions', 'getPermissionConditions'],
            [new RemoveDefaultSearchCondition(), 'setSearchConditions', 'getSearchConditions'],
            [new RemoveDefaultSoftDeleteCondition(), 'setSoftDeleteConditions', 'getSoftDeleteConditions'],
        ];

        foreach ($cases as [$behavior, $setter, $getter]) {
            $controller = $this->newController();
            $controller->{$setter}(new Collection([
                'default' => 'default-condition',
                'custom' => 'custom-condition',
            ], false));

            $behavior->afterInitializeConditions($this->newEvent($controller), $controller);

            $this->assertFalse($controller->{$getter}()->has('default'));
            $this->assertSame('custom-condition', $controller->{$getter}()->get('custom'));
        }
    }

    public function testConditionBehaviorsClearTheirCollections(): void
    {
        $cases = [
            [new RemoveFilterConditions(), 'setFilterConditions', 'getFilterConditions'],
            [new RemoveIdentityConditions(), 'setIdentityConditions', 'getIdentityConditions'],
            [new RemovePermissionConditions(), 'setPermissionConditions', 'getPermissionConditions'],
            [new RemoveSearchConditions(), 'setSearchConditions', 'getSearchConditions'],
            [new RemoveSoftDeleteConditions(), 'setSoftDeleteConditions', 'getSoftDeleteConditions'],
        ];

        foreach ($cases as [$behavior, $setter, $getter]) {
            $controller = $this->newController();
            $controller->{$setter}(new Collection(['default' => 'value'], false));

            $behavior->afterInitializeConditions($this->newEvent($controller), $controller);

            $this->assertSame([], $controller->{$getter}()->toArray());
        }
    }

    public function testSoftDeleteWhileFilteringBehaviorsOnlyRunWhenDeletedFilterIsPresent(): void
    {
        $controller = $this->newFilteringController(false);
        $controller->setSoftDeleteConditions(new Collection([
            'default' => 'default-condition',
            'custom' => 'custom-condition',
        ], false));

        (new RemoveDefaultSoftDeleteConditionWhileFiltering())->afterInitializeConditions(
            $this->newEvent($controller),
            $controller
        );

        $this->assertTrue($controller->getSoftDeleteConditions()->has('default'));
        $this->assertSame('custom-condition', $controller->getSoftDeleteConditions()->get('custom'));

        $controller = $this->newFilteringController(true);
        $controller->setSoftDeleteConditions(new Collection([
            'default' => 'default-condition',
            'custom' => 'custom-condition',
        ], false));

        (new RemoveDefaultSoftDeleteConditionWhileFiltering())->afterInitializeConditions(
            $this->newEvent($controller),
            $controller
        );

        $this->assertFalse($controller->getSoftDeleteConditions()->has('default'));
        $this->assertSame('custom-condition', $controller->getSoftDeleteConditions()->get('custom'));

        $controller = $this->newFilteringController(true);
        $controller->setSoftDeleteConditions(new Collection([
            'default' => 'default-condition',
            'custom' => 'custom-condition',
        ], false));

        (new RemoveSoftDeleteConditionsWhileFiltering())->afterInitializeConditions(
            $this->newEvent($controller),
            $controller
        );

        $this->assertSame([], $controller->getSoftDeleteConditions()->toArray());
    }

    public function testLimitBehaviorsAdjustLimitState(): void
    {
        $controller = $this->newController();
        $controller->setMaxLimit(50);
        $controller->setLimit(10);

        (new RemoveLimit())->afterInitializeLimit($this->newEvent($controller), $controller);

        $this->assertSame(50, $controller->getLimit());

        (new RemoveMaxLimit())->beforeInitializeQuery($this->newEvent($controller), $controller);

        $this->assertSame(-1, $controller->getMaxLimit());

        (new RemoveDefaultLimit())->beforeInitializeQuery($this->newEvent($controller), $controller);

        $this->assertSame(-1, $controller->getMaxLimit());
        $this->assertNull($controller->getLimit());
    }

    public function testRemoveOffsetResetsOffsetToZero(): void
    {
        $controller = $this->newController();
        $controller->setOffset(25);

        (new RemoveOffset())->afterInitializeOffset($this->newEvent($controller), $controller);

        $this->assertSame(0, $controller->getOffset());
    }

    private function newController(): Restful
    {
        return new class extends Restful {
            public function initialize(): void
            {
            }
        };
    }

    private function newFilteringController(bool $hasDeletedFilter): Restful
    {
        $controller = new class extends Restful {
            public bool $hasDeletedFilter = false;

            public function initialize(): void
            {
            }

            public function hasFiltersFieldsParams(array|string|null $fields = null, bool $or = false): bool
            {
                return $this->hasDeletedFilter;
            }
        };

        $controller->hasDeletedFilter = $hasDeletedFilter;

        return $controller;
    }

    private function newEvent(Restful $controller): Event
    {
        return new Event('rest:test', $controller);
    }
}
