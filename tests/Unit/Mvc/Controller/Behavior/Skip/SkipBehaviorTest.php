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

namespace PhalconKit\Tests\Unit\Mvc\Controller\Behavior\Skip;

use Phalcon\Events\Event;
use Phalcon\Support\Collection;
use PhalconKit\Mvc\Controller\Behavior\Skip\SkipBind;
use PhalconKit\Mvc\Controller\Behavior\Skip\SkipBindTypes;
use PhalconKit\Mvc\Controller\Behavior\Skip\SkipCache;
use PhalconKit\Mvc\Controller\Behavior\Skip\SkipColumns;
use PhalconKit\Mvc\Controller\Behavior\Skip\SkipConditions;
use PhalconKit\Mvc\Controller\Behavior\Skip\SkipDistinct;
use PhalconKit\Mvc\Controller\Behavior\Skip\SkipFilterCondition;
use PhalconKit\Mvc\Controller\Behavior\Skip\SkipGroup;
use PhalconKit\Mvc\Controller\Behavior\Skip\SkipHaving;
use PhalconKit\Mvc\Controller\Behavior\Skip\SkipIdentityCondition;
use PhalconKit\Mvc\Controller\Behavior\Skip\SkipJoins;
use PhalconKit\Mvc\Controller\Behavior\Skip\SkipLimit;
use PhalconKit\Mvc\Controller\Behavior\Skip\SkipOffset;
use PhalconKit\Mvc\Controller\Behavior\Skip\SkipOrder;
use PhalconKit\Mvc\Controller\Behavior\Skip\SkipPermissionCondition;
use PhalconKit\Mvc\Controller\Behavior\Skip\SkipSearchCondition;
use PhalconKit\Mvc\Controller\Behavior\Skip\SkipSoftDeleteCondition;
use PhalconKit\Mvc\Controller\Behavior\Skip\SkipWhiteList;
use PhalconKit\Mvc\Controller\Restful;
use PhalconKit\Tests\Unit\AbstractUnit;

class SkipBehaviorTest extends AbstractUnit
{
    public function testLegacySkipBehaviorsReturnFalseForTheirGetter(): void
    {
        $cases = [
            [new SkipBind(), 'getBind'],
            [new SkipBindTypes(), 'getBindTypes'],
            [new SkipCache(), 'getCache'],
            [new SkipColumns(), 'getColumns'],
            [new SkipConditions(), 'getConditions'],
            [new SkipDistinct(), 'getDistinct'],
            [new SkipFilterCondition(), 'getFilterCondition'],
            [new SkipGroup(), 'getGroup'],
            [new SkipHaving(), 'getHaving'],
            [new SkipIdentityCondition(), 'getIdentityCondition'],
            [new SkipJoins(), 'getJoins'],
            [new SkipLimit(), 'getLimit'],
            [new SkipOffset(), 'getOffset'],
            [new SkipOrder(), 'getOrder'],
            [new SkipPermissionCondition(), 'getPermissionConditions'],
            [new SkipSearchCondition(), 'getSearchCondition'],
            [new SkipWhiteList(), 'getWhiteList'],
        ];

        foreach ($cases as [$behavior, $method]) {
            $this->assertFalse($behavior->{$method}());
        }
    }

    public function testSkipSoftDeleteConditionRemovesSoftDeleteGroup(): void
    {
        $controller = $this->newController();
        $controller->setConditions(new Collection([
            'permission' => ['permission condition'],
            'softDelete' => ['soft delete condition'],
        ], false));

        (new SkipSoftDeleteCondition())->afterConditions(
            new Event('rest:afterConditions', $controller),
            $controller
        );

        $this->assertTrue($controller->getConditions()->has('permission'));
        $this->assertFalse($controller->getConditions()->has('softDelete'));
    }

    private function newController(): Restful
    {
        return new class extends Restful {
            public function initialize(): void
            {
            }
        };
    }
}
