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

namespace PhalconKit\Tests\Unit\Mvc\Controller\Traits\Actions\Rest;

use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Support\Collection;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\DistinctActionControllerDouble;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\DistinctActionViewDouble;

class DistinctActionTest extends AbstractUnit
{
    public function testDistinctActionRequiresFieldParameter(): void
    {
        $controller = $this->createController(['status']);

        $controller->distinctAction();

        $this->assertSame(400, $controller->restErrorCode);
        $this->assertSame(
            ['Distinct field is required.'],
            $controller->view->getVar(DistinctActionControllerDouble::REST_VIEW_MESSAGES)
        );
        $this->assertSame([], $controller->findFinds);
    }

    public function testDistinctActionRequiresAllowedField(): void
    {
        $controller = $this->createController(['status'], ['field' => 'email']);

        $controller->distinctAction();

        $this->assertSame(400, $controller->restErrorCode);
        $this->assertSame(
            ['Distinct field is not allowed.'],
            $controller->view->getVar(DistinctActionControllerDouble::REST_VIEW_MESSAGES)
        );
        $this->assertSame([], $controller->findFinds);
    }

    public function testDistinctActionReturnsValuesForAllowedField(): void
    {
        $controller = $this->createController(['status'], ['field' => 'status']);
        $controller->findResults = [
            $this->createDistinctResultset([
                ['value' => 'closed'],
                ['value' => 'open'],
                ['value' => null],
            ]),
        ];
        $controller->setFind(new Collection([
            'conditions' => 'active = 1',
            'bind' => ['active' => 1],
            'bindTypes' => ['active' => 5],
            'limit' => 10,
            'offset' => 20,
            'order' => 'id desc',
            'column' => new Collection(['id' => true], false),
            'columns' => 'id',
            'distinct' => new Collection(['type' => true], false),
            'group' => new Collection(['type' => true], false),
            'having' => 'COUNT(id) > 1',
            'cache' => ['lifetime' => 60],
        ], false));

        $controller->distinctAction();

        $this->assertTrue($controller->restResponse);
        $this->assertSame(
            ['closed', 'open', null],
            $controller->view->getVar(DistinctActionControllerDouble::REST_VIEW_DATA)
        );
        $this->assertSame('status', $controller->view->getVar(DistinctActionControllerDouble::REST_VIEW_FIELD));
        $this->assertSame(3, $controller->view->getVar(DistinctActionControllerDouble::REST_VIEW_COUNT));
        $this->assertSame([
            [
                'limit' => 10,
                'offset' => 20,
                'conditions' => '(active = 1)',
                'order' => '[FooModel].[status] ASC',
                'bind' => ['active' => 1],
                'bindTypes' => ['active' => 5],
                'cache' => ['lifetime' => 60],
                'columns' => 'DISTINCT [FooModel].[status] AS value',
            ],
        ], $controller->findFinds);
    }

    public function testDistinctActionSupportsPublicAliasForJoinedField(): void
    {
        $controller = $this->createController(
            ['ownerEmail' => 'Owner.email'],
            ['field' => 'ownerEmail']
        );
        $controller->findResults = [
            $this->createDistinctResultset([
                ['value' => 'owner@example.com'],
            ]),
        ];

        $controller->distinctAction();

        $this->assertSame('ownerEmail', $controller->view->getVar(DistinctActionControllerDouble::REST_VIEW_FIELD));
        $this->assertSame(['owner@example.com'], $controller->view->getVar(DistinctActionControllerDouble::REST_VIEW_DATA));
        $this->assertSame([
            [
                'columns' => 'DISTINCT [Owner].[email] AS value',
                'order' => '[Owner].[email] ASC',
            ],
        ], $controller->findFinds);
    }

    public function testDistinctActionFieldPolicyUsesCollectionLifecycle(): void
    {
        $controller = $this->createController();

        $controller->initializeDistinctActionFields();

        $this->assertFalse($controller->hasDistinctActionFields());
        $this->assertNull($controller->getDistinctActionFields());

        $controller->setDistinctActionFields(new Collection([
            'status',
        ], false));

        $this->assertTrue($controller->hasDistinctActionFields());
        $this->assertSame(['status'], $controller->getDistinctActionFields()?->toArray());

        $controller->mergeDistinctActionFields(new Collection([
            'ownerEmail' => 'Owner.email',
        ], false));

        $this->assertSame(
            [
                'status',
                'ownerEmail' => 'Owner.email',
            ],
            $controller->getDistinctActionFields()?->toArray()
        );

        $controller->setDistinctActionFields(new Collection([
            'status' => false,
        ], false));

        $this->assertFalse($controller->hasDistinctActionFields());
    }

    /**
     * Build a controller wired for distinct action response tests.
     *
     * @param array<string|int, mixed> $fields Distinct field policy configured
     *     by the concrete controller.
     * @param array<string, mixed> $params Request parameters returned by the
     *     controller double.
     */
    private function createController(array $fields = [], array $params = []): DistinctActionControllerDouble
    {
        $controller = new DistinctActionControllerDouble();
        $controller->view = new DistinctActionViewDouble();
        $controller->params = $params;
        $controller->setModelName('FooModel');
        $controller->setUnitDistinctActionFields($fields);

        return $controller;
    }

    /**
     * Create a distinct resultset stub.
     *
     * @param list<array<string, mixed>> $rows
     */
    private function createDistinctResultset(array $rows): ResultsetInterface
    {
        $resultset = $this->createStub(ResultsetInterface::class);
        $resultset
            ->method('toArray')
            ->willReturn($rows);

        return $resultset;
    }
}
