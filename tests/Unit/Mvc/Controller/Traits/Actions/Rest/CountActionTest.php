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
use PhalconKit\Exception\HttpException;
use PhalconKit\Mvc\Controller\Restful;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\CountActionControllerDouble;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\CountActionViewDouble;

class CountActionTest extends AbstractUnit
{
    public function testCountActionPreservesDefaultNativeCountResponseOnly(): void
    {
        $controller = $this->createController([7]);

        $controller->countAction();

        $this->assertSame(7, $controller->view->getVar(CountActionControllerDouble::REST_VIEW_COUNT));
        $this->assertNull($controller->view->getVar(CountActionControllerDouble::COUNT_RESPONSE_GROUPED_COUNT));
        $this->assertNull($controller->view->getVar(CountActionControllerDouble::COUNT_RESPONSE_BUCKET_TOTAL));
        $this->assertNull($controller->view->getVar(CountActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT));
        $this->assertSame([null], $controller->countFinds);
        $this->assertTrue($controller->restResponse);
    }

    public function testCountActionCanExposeExplicitGroupedBucketAndTotalCounts(): void
    {
        $groupedCount = $this->createGroupedCountResultset([
            ['status' => 'open', 'rowcount' => 3],
            ['status' => 'closed', 'rowcount' => '4'],
        ]);
        $controller = $this->createController([$groupedCount, 5], [
            CountActionControllerDouble::COUNT_RESPONSE_GROUPED_COUNT,
            CountActionControllerDouble::COUNT_RESPONSE_BUCKET_TOTAL,
            CountActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT,
        ]);
        $controller->setFind(new Collection([
            'conditions' => 'active = 1',
            'bind' => ['active' => 1],
            'bindTypes' => ['active' => 5],
            'group' => new Collection(['status']),
            'limit' => 10,
            'offset' => 20,
        ]));

        $controller->countAction();

        $this->assertSame($groupedCount, $controller->view->getVar(CountActionControllerDouble::REST_VIEW_COUNT));
        $this->assertSame($groupedCount, $controller->view->getVar(CountActionControllerDouble::COUNT_RESPONSE_GROUPED_COUNT));
        $this->assertSame(7, $controller->view->getVar(CountActionControllerDouble::COUNT_RESPONSE_BUCKET_TOTAL));
        $this->assertSame(5, $controller->view->getVar(CountActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT));
        $this->assertSame([
            null,
            [
                'conditions' => '(active = 1)',
                'bind' => ['active' => 1],
                'bindTypes' => ['active' => 5],
            ],
        ], $controller->countFinds);
    }

    public function testCountActionCanRequestTotalCountWhenPolicyIsUnrestricted(): void
    {
        $controller = $this->createController([7, 11], params: [
            'count' => 'totalCount',
        ]);

        $controller->countAction();

        $this->assertSame(7, $controller->view->getVar(CountActionControllerDouble::REST_VIEW_COUNT));
        $this->assertNull($controller->view->getVar(CountActionControllerDouble::COUNT_RESPONSE_GROUPED_COUNT));
        $this->assertNull($controller->view->getVar(CountActionControllerDouble::COUNT_RESPONSE_BUCKET_TOTAL));
        $this->assertSame(11, $controller->view->getVar(CountActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT));
        $this->assertSame([null, []], $controller->countFinds);
    }

    public function testCountActionCanRequestGroupedBucketAndTotalCounts(): void
    {
        $groupedCount = $this->createGroupedCountResultset([
            ['status' => 'open', 'rowcount' => 3],
            ['status' => 'closed', 'rowcount' => '4'],
        ]);
        $controller = $this->createController([$groupedCount, 5], params: [
            'count' => 'count,groupedCount,bucketTotal,totalCount',
        ]);
        $controller->setFind(new Collection([
            'conditions' => 'active = 1',
            'bind' => ['active' => 1],
            'bindTypes' => ['active' => 5],
            'group' => new Collection(['status']),
            'limit' => 10,
            'offset' => 20,
        ]));

        $controller->countAction();

        $this->assertSame($groupedCount, $controller->view->getVar(CountActionControllerDouble::REST_VIEW_COUNT));
        $this->assertSame($groupedCount, $controller->view->getVar(CountActionControllerDouble::COUNT_RESPONSE_GROUPED_COUNT));
        $this->assertSame(7, $controller->view->getVar(CountActionControllerDouble::COUNT_RESPONSE_BUCKET_TOTAL));
        $this->assertSame(5, $controller->view->getVar(CountActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT));
        $this->assertSame([
            null,
            [
                'conditions' => '(active = 1)',
                'bind' => ['active' => 1],
                'bindTypes' => ['active' => 5],
            ],
        ], $controller->countFinds);
    }

    public function testCountActionRequestUsesListAndEnabledMapSyntax(): void
    {
        $controller = $this->createController([7, 11], params: [
            'count' => [
                CountActionControllerDouble::REST_VIEW_COUNT,
                CountActionControllerDouble::COUNT_RESPONSE_BUCKET_TOTAL => 0,
                CountActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT => '1',
                CountActionControllerDouble::COUNT_RESPONSE_GROUPED_COUNT => 'off',
                'ignored-empty' => false,
            ],
        ]);

        $controller->countAction();

        $this->assertSame(7, $controller->view->getVar(CountActionControllerDouble::REST_VIEW_COUNT));
        $this->assertNull($controller->view->getVar(CountActionControllerDouble::COUNT_RESPONSE_GROUPED_COUNT));
        $this->assertNull($controller->view->getVar(CountActionControllerDouble::COUNT_RESPONSE_BUCKET_TOTAL));
        $this->assertSame(11, $controller->view->getVar(CountActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT));
        $this->assertSame([null, []], $controller->countFinds);
    }

    public function testCountActionTruthyCountRequestDoesNotAddExtraFields(): void
    {
        $controller = $this->createController([7], params: [
            'count' => '1',
        ]);

        $controller->countAction();

        $this->assertSame(7, $controller->view->getVar(CountActionControllerDouble::REST_VIEW_COUNT));
        $this->assertNull($controller->view->getVar(CountActionControllerDouble::COUNT_RESPONSE_GROUPED_COUNT));
        $this->assertNull($controller->view->getVar(CountActionControllerDouble::COUNT_RESPONSE_BUCKET_TOTAL));
        $this->assertNull($controller->view->getVar(CountActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT));
        $this->assertSame([null], $controller->countFinds);
    }

    public function testCountActionRejectsDisallowedRequestedResponseField(): void
    {
        $controller = $this->createController([7], [
            CountActionControllerDouble::COUNT_RESPONSE_BUCKET_TOTAL,
        ], [
            'count' => 'totalCount',
        ]);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized count response field "totalCount".');

        $controller->countAction();
    }

    public function testCountActionRejectsUnsupportedRequestedResponseField(): void
    {
        $controller = $this->createController([7], params: [
            'count' => 'unknownCount',
        ]);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized count response field "unknownCount".');

        $controller->countAction();
    }

    public function testCountActionRejectsInvalidCountParameterType(): void
    {
        $controller = $this->createController([7], params: [
            'count' => new \stdClass(),
        ]);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Invalid type for "count" parameter: expected null, bool, string, or array, got object.');

        $controller->countAction();
    }

    public function testRestfulInitializeIncludesRestActionInitialization(): void
    {
        $controller = new class extends Restful {
            public bool $queryInitialized = false;
            public bool $countActionResponseFieldsInitialized = false;
            public bool $distinctActionFieldsInitialized = false;
            public bool $findActionCountFieldsInitialized = false;

            public function initializeQuery(): void
            {
                $this->queryInitialized = true;
            }

            public function initializeCountActionResponseFields(): void
            {
                $this->countActionResponseFieldsInitialized = true;
            }

            public function initializeDistinctActionFields(): void
            {
                $this->distinctActionFieldsInitialized = true;
            }

            public function initializeFindActionCountFields(): void
            {
                $this->findActionCountFieldsInitialized = true;
            }
        };

        $controller->initialize();

        $this->assertTrue($controller->queryInitialized);
        $this->assertTrue($controller->countActionResponseFieldsInitialized);
        $this->assertTrue($controller->distinctActionFieldsInitialized);
        $this->assertTrue($controller->findActionCountFieldsInitialized);
    }

    public function testCountActionResponseFieldsUseCollectionPolicy(): void
    {
        $controller = $this->createController([7]);

        $controller->initializeCountActionResponseFields();

        $this->assertFalse($controller->hasCountActionResponseFields());
        $this->assertNull($controller->getCountActionResponseFields());

        $controller->setCountActionResponseFields(new Collection([
            CountActionControllerDouble::COUNT_RESPONSE_GROUPED_COUNT,
        ], false));

        $this->assertTrue($controller->hasCountActionResponseFields());
        $this->assertSame(
            [CountActionControllerDouble::COUNT_RESPONSE_GROUPED_COUNT],
            $controller->getCountActionResponseFields()?->toArray()
        );

        $controller->mergeCountActionResponseFields(new Collection([
            CountActionControllerDouble::COUNT_RESPONSE_BUCKET_TOTAL,
        ], false));

        $this->assertSame(
            [
                CountActionControllerDouble::COUNT_RESPONSE_GROUPED_COUNT,
                CountActionControllerDouble::COUNT_RESPONSE_BUCKET_TOTAL,
            ],
            $controller->getCountActionResponseFields()?->toArray()
        );

        $controller->setCountActionResponseFields(new Collection([
            CountActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT => false,
        ], false));

        $this->assertFalse($controller->hasCountActionResponseFields());
    }

    public function testCountActionResponseFieldsNormalizeEnabledMapValues(): void
    {
        $controller = $this->createController([7, 11]);
        $controller->setCountActionResponseFields(new Collection([
            CountActionControllerDouble::COUNT_RESPONSE_GROUPED_COUNT => 'off',
            CountActionControllerDouble::COUNT_RESPONSE_BUCKET_TOTAL => 0,
            CountActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT => 'yes',
        ], false));

        $controller->countAction();

        $this->assertSame(7, $controller->view->getVar(CountActionControllerDouble::REST_VIEW_COUNT));
        $this->assertNull($controller->view->getVar(CountActionControllerDouble::COUNT_RESPONSE_GROUPED_COUNT));
        $this->assertNull($controller->view->getVar(CountActionControllerDouble::COUNT_RESPONSE_BUCKET_TOTAL));
        $this->assertSame(11, $controller->view->getVar(CountActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT));
        $this->assertSame([null, []], $controller->countFinds);
    }

    public function testCountActionBucketTotalRefusesAmbiguousGroupedRows(): void
    {
        $groupedCount = $this->createGroupedCountResultset([
            ['status' => 'open', 'first' => 3, 'second' => 4],
        ]);
        $controller = $this->createController([$groupedCount], [
            CountActionControllerDouble::COUNT_RESPONSE_BUCKET_TOTAL,
        ]);

        $controller->countAction();

        $this->assertFalse($controller->view->getVar(CountActionControllerDouble::COUNT_RESPONSE_BUCKET_TOTAL));
    }

    public function testCountActionBucketTotalDoesNotSumNumericGroupKeys(): void
    {
        $groupedCount = $this->createGroupedCountResultset([
            ['statusId' => 3, 'label' => 'open'],
        ]);
        $controller = $this->createController([$groupedCount], [
            CountActionControllerDouble::COUNT_RESPONSE_BUCKET_TOTAL,
        ]);

        $controller->countAction();

        $this->assertFalse($controller->view->getVar(CountActionControllerDouble::COUNT_RESPONSE_BUCKET_TOTAL));
    }

    /**
     * Build a controller wired for count action response tests.
     *
     * @param list<ResultsetInterface|int|false> $countResults Results returned
     *     by each call to count().
     * @param list<string> $responseFields Extra count fields enabled by the
     *     concrete controller.
     * @param array<string, mixed> $params Synthetic request params.
     */
    private function createController(
        array $countResults,
        array $responseFields = [],
        array $params = []
    ): CountActionControllerDouble {
        $controller = new CountActionControllerDouble();
        $controller->view = new CountActionViewDouble();
        $controller->countResults = $countResults;
        $controller->unitParams = $params;
        $controller->setUnitCountActionResponseFields($responseFields);

        return $controller;
    }

    /**
     * Create a grouped count resultset stub.
     *
     * @param list<array<string, mixed>> $rows
     */
    private function createGroupedCountResultset(array $rows): ResultsetInterface
    {
        $resultset = $this->createStub(ResultsetInterface::class);
        $resultset
            ->method('toArray')
            ->willReturn($rows);

        return $resultset;
    }
}
