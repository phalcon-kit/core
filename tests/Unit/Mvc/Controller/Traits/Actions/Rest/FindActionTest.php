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
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\CountActionViewDouble;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\FindActionControllerDouble;

class FindActionTest extends AbstractUnit
{
    public function testFindActionDoesNotCountUntilClientRequestsIt(): void
    {
        $controller = $this->createController([], [7]);

        $controller->findAction();

        $this->assertTrue($controller->findCalled);
        $this->assertSame([['id' => 1]], $controller->view->getVar(FindActionControllerDouble::REST_VIEW_DATA));
        $this->assertNull($controller->view->getVar(FindActionControllerDouble::REST_VIEW_COUNT));
        $this->assertSame([], $controller->countFinds);
        $this->assertTrue($controller->restResponse);
    }

    public function testFindActionCanEmbedPlainCountWhenPolicyIsUnrestricted(): void
    {
        $controller = $this->createController(['count' => '1'], [7]);

        $controller->findAction();

        $this->assertTrue($controller->findCalled);
        $this->assertSame([['id' => 1]], $controller->view->getVar(FindActionControllerDouble::REST_VIEW_DATA));
        $this->assertSame(7, $controller->view->getVar(FindActionControllerDouble::REST_VIEW_COUNT));
        $this->assertSame([[]], $controller->countFinds);
        $this->assertTrue($controller->restResponse);
    }

    public function testFindActionCanEmbedPlainCountWhenRequestedAndAllowed(): void
    {
        $controller = $this->createController(['count' => 'true'], [7], [
            FindActionControllerDouble::REST_VIEW_COUNT,
        ]);

        $controller->findAction();

        $this->assertSame([['id' => 1]], $controller->view->getVar(FindActionControllerDouble::REST_VIEW_DATA));
        $this->assertSame(7, $controller->view->getVar(FindActionControllerDouble::REST_VIEW_COUNT));
        $this->assertSame([[]], $controller->countFinds);
        $this->assertTrue($controller->restResponse);
    }

    public function testFindActionCountHonorsFiltersAndIgnoresPagination(): void
    {
        $controller = $this->createController(['count' => 'count'], [12], [
            FindActionControllerDouble::REST_VIEW_COUNT,
        ]);
        $controller->setFind(new Collection([
            'conditions' => 'active = 1',
            'bind' => ['active' => 1],
            'bindTypes' => ['active' => 5],
            'limit' => 10,
            'offset' => 20,
        ]));

        $controller->findAction();

        $this->assertSame(12, $controller->view->getVar(FindActionControllerDouble::REST_VIEW_COUNT));
        $this->assertSame([
            [
                'conditions' => '(active = 1)',
                'bind' => ['active' => 1],
                'bindTypes' => ['active' => 5],
            ],
        ], $controller->countFinds);
    }

    public function testFindActionCanEmbedGroupedBucketAndTotalCounts(): void
    {
        $groupedCount = $this->createGroupedCountResultset([
            ['status' => 'open', 'rowcount' => 3],
            ['status' => 'closed', 'rowcount' => '4'],
        ]);
        $controller = $this->createController(['count' => 'count,bucketTotal,totalCount'], [$groupedCount, 5], [
            FindActionControllerDouble::REST_VIEW_COUNT,
            FindActionControllerDouble::COUNT_RESPONSE_BUCKET_TOTAL,
            FindActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT,
        ]);
        $controller->setFind(new Collection([
            'conditions' => 'active = 1',
            'bind' => ['active' => 1],
            'bindTypes' => ['active' => 5],
            'group' => new Collection(['status']),
            'limit' => 10,
            'offset' => 20,
        ]));

        $controller->findAction();

        $this->assertSame($groupedCount, $controller->view->getVar(FindActionControllerDouble::REST_VIEW_COUNT));
        $this->assertSame(7, $controller->view->getVar(FindActionControllerDouble::COUNT_RESPONSE_BUCKET_TOTAL));
        $this->assertSame(5, $controller->view->getVar(FindActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT));
        $this->assertSame([
            [
                'conditions' => '(active = 1)',
                'group' => 'status',
                'bind' => ['active' => 1],
                'bindTypes' => ['active' => 5],
            ],
            [
                'conditions' => '(active = 1)',
                'bind' => ['active' => 1],
                'bindTypes' => ['active' => 5],
            ],
        ], $controller->countFinds);
    }

    public function testFindActionRejectsDisallowedRequestedCountField(): void
    {
        $controller = $this->createController(['count' => 'totalCount'], [7], [
            FindActionControllerDouble::REST_VIEW_COUNT,
        ]);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized list count field "totalCount".');

        $controller->findAction();
    }

    public function testFindActionRejectsUnsupportedCountFieldInUnrestrictedMode(): void
    {
        $controller = $this->createController(['count' => 'unknownCount'], [7]);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized list count field "unknownCount".');

        $controller->findAction();
    }

    public function testFindActionRejectsCountFieldWhenControllerPolicyIsEmpty(): void
    {
        $controller = $this->createController(['count' => 'count'], [7]);
        $controller->setFindActionCountFields(new Collection([], false));

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized list count field "count".');

        $controller->findAction();
    }

    public function testFindWithActionUsesSameEmbeddedCountPolicy(): void
    {
        $controller = $this->createController(['count' => 'totalCount'], [9], [
            FindActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT,
        ]);
        $controller->findWithResult = [['model' => 'loaded']];
        $controller->exposedData = [['id' => 99]];

        $controller->findWithAction();

        $this->assertTrue($controller->findWithCalled);
        $this->assertSame([['id' => 99]], $controller->view->getVar(FindActionControllerDouble::REST_VIEW_DATA));
        $this->assertSame(9, $controller->view->getVar(FindActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT));
        $this->assertSame([[]], $controller->countFinds);
    }

    public function testFindActionAcceptsArrayCountRequestSyntax(): void
    {
        $controller = $this->createController([
            'count' => [
                FindActionControllerDouble::REST_VIEW_COUNT,
                FindActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT => '1',
            ],
        ], [7, 9], [
            FindActionControllerDouble::REST_VIEW_COUNT,
            FindActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT,
        ]);

        $controller->findAction();

        $this->assertSame(7, $controller->view->getVar(FindActionControllerDouble::REST_VIEW_COUNT));
        $this->assertSame(9, $controller->view->getVar(FindActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT));
        $this->assertSame([[], []], $controller->countFinds);
    }

    public function testFindActionCountFieldsUseCollectionPolicy(): void
    {
        $controller = $this->createController();

        $controller->initializeFindActionCountFields();

        $this->assertFalse($controller->hasFindActionCountFields());
        $this->assertNull($controller->getFindActionCountFields());

        $controller->setFindActionCountFields(new Collection([
            FindActionControllerDouble::REST_VIEW_COUNT,
        ], false));

        $this->assertTrue($controller->hasFindActionCountFields());
        $this->assertSame(
            [FindActionControllerDouble::REST_VIEW_COUNT],
            $controller->getFindActionCountFields()?->toArray()
        );

        $controller->mergeFindActionCountFields(new Collection([
            FindActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT,
        ], false));

        $this->assertSame(
            [
                FindActionControllerDouble::REST_VIEW_COUNT,
                FindActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT,
            ],
            $controller->getFindActionCountFields()?->toArray()
        );

        $controller->setFindActionCountFields(new Collection([
            FindActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT => false,
        ], false));

        $this->assertTrue($controller->hasFindActionCountFields());
    }

    /**
     * Build a controller wired for find action response tests.
     *
     * @param array<string, mixed> $params Synthetic request parameters.
     * @param list<ResultsetInterface|int|false> $countResults Results returned
     *     by each call to count().
     * @param list<string> $countFields Optional explicit embedded count policy;
     *     an empty list leaves the controller in unrestricted null-policy mode.
     */
    private function createController(
        array $params = [],
        array $countResults = [],
        array $countFields = []
    ): FindActionControllerDouble {
        $controller = new FindActionControllerDouble();
        $controller->view = new CountActionViewDouble();
        $controller->unitParams = $params;
        $controller->findResult = $this->createStub(ResultsetInterface::class);
        $controller->exposedData = [['id' => 1]];
        $controller->countResults = $countResults;
        $controller->setUnitFindActionCountFields($countFields);

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
