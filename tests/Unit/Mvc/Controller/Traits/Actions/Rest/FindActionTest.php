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
use Phalcon\Mvc\ModelInterface;
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

    public function testFindActionNeverLoadsRequestedRelationships(): void
    {
        $controller = $this->createController(['with' => 'Author']);

        $controller->findAction();

        $this->assertTrue($controller->findCalled);
        $this->assertFalse($controller->findWithCalled);
        $this->assertSame([['id' => 1]], $controller->view->getVar(FindActionControllerDouble::REST_VIEW_DATA));
    }

    public function testFindWithActionUsesDefaultRelationshipsWhenRequestDoesNotSpecifyWith(): void
    {
        $controller = $this->createController();
        $controller->setWith(new Collection(['Author', 'Author.Profile'], false));
        $controller->findWithResult = [['model' => 'loaded']];
        $controller->exposedData = [['id' => 2]];

        $controller->findWithAction();

        $this->assertTrue($controller->findWithCalled);
        $this->assertSame(['with' => null, 'find' => null], $controller->findWithArguments);
        $this->assertSame([['id' => 2]], $controller->view->getVar(FindActionControllerDouble::REST_VIEW_DATA));
    }

    public function testFindWithActionUsesOnlyRequestedAllowedRelationships(): void
    {
        $controller = $this->createController(['with' => 'Author.Profile']);
        $controller->setWith(new Collection(['Author', 'Author.Profile', 'Comments'], false));
        $controller->findWithResult = [['model' => 'loaded']];

        $controller->findWithAction();

        $this->assertSame(['with' => ['Author.Profile'], 'find' => null], $controller->findWithArguments);
    }

    public function testFindWithActionTreatsPresentEmptyWithAsNoRelationships(): void
    {
        $controller = $this->createController(['with' => '']);
        $controller->setWith(new Collection(['Author', 'Author.Profile'], false));
        $controller->findWithResult = [['model' => 'loaded']];

        $controller->findWithAction();

        $this->assertSame(['with' => [], 'find' => null], $controller->findWithArguments);

        $controller = $this->createController(['with' => false]);
        $controller->setWith(new Collection(['Author'], false));
        $controller->findWithResult = [['model' => 'loaded']];

        $controller->findWithAction();

        $this->assertSame(['with' => [], 'find' => null], $controller->findWithArguments);
    }

    public function testFindWithActionNormalizesEnabledMapSyntax(): void
    {
        $constraint = static fn(): null => null;
        $controller = $this->createController([
            'with' => [
                'Author.Profile' => 'yes',
                'Comments' => 'off',
                'Audit' => 0,
            ],
        ]);
        $controller->setWith(new Collection([
            'Author' => $constraint,
            'Author.Profile',
            'Comments',
            'Audit',
        ], false));
        $controller->findWithResult = [['model' => 'loaded']];

        $controller->findWithAction();

        $this->assertSame([
            'with' => [
                'Author' => $constraint,
                'Author.Profile',
            ],
            'find' => null,
        ], $controller->findWithArguments);
    }

    public function testFindWithActionRejectsRequestForDisabledConfiguredRelationship(): void
    {
        $controller = $this->createController(['with' => 'Comments']);
        $controller->setWith(new Collection([
            'Author',
            'Comments' => 'off',
            'Audit' => 0,
        ], false));

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized relationship "Comments".');

        $controller->findWithAction();
    }

    public function testFindWithActionAllowsParentOfConfiguredNestedRelationship(): void
    {
        $controller = $this->createController(['with' => 'Author.Profile']);
        $controller->setWith(new Collection(['Author.Profile.Avatar'], false));
        $controller->findWithResult = [['model' => 'loaded']];

        $controller->findWithAction();

        $this->assertSame(['with' => ['Author.Profile'], 'find' => null], $controller->findWithArguments);
    }

    public function testFindWithActionPreservesConfiguredParentConstraintsForNestedRequest(): void
    {
        $constraint = static fn(): null => null;
        $controller = $this->createController([
            'with' => [
                'Author.Profile' => '1',
            ],
        ]);
        $controller->setWith(new Collection([
            'Author' => $constraint,
            'Author.Profile' => true,
            'Comments',
        ], false));
        $controller->findWithResult = [['model' => 'loaded']];

        $controller->findWithAction();

        $this->assertSame([
            'with' => [
                'Author' => $constraint,
                'Author.Profile',
            ],
            'find' => null,
        ], $controller->findWithArguments);
    }

    public function testFindWithActionRejectsRelationshipOutsideConfiguredGraph(): void
    {
        $controller = $this->createController(['with' => 'Author.Profile']);
        $controller->setWith(new Collection(['Author'], false));

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized relationship "Author.Profile".');

        $controller->findWithAction();
    }

    public function testFindWithActionRejectsRequestedRelationshipWithoutConfiguredGraph(): void
    {
        $controller = $this->createController(['with' => 'Author']);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized relationship "Author".');

        $controller->findWithAction();
    }

    public function testFindWithActionRejectsInvalidWithParameterShapes(): void
    {
        $controller = $this->createController(['with' => true]);
        $controller->setWith(new Collection(['Author'], false));

        try {
            $controller->findWithAction();
            $this->fail('Expected invalid scalar with parameter type.');
        }
        catch (HttpException $exception) {
            $this->assertSame(400, $exception->getCode());
            $this->assertSame(
                'Invalid type for "with" parameter: expected null, bool, string, or array, got boolean.',
                $exception->getMessage()
            );
        }

        $controller = $this->createController(['with' => [['Author']]]);
        $controller->setWith(new Collection(['Author'], false));

        try {
            $controller->findWithAction();
            $this->fail('Expected invalid list with parameter value.');
        }
        catch (HttpException $exception) {
            $this->assertSame(400, $exception->getCode());
            $this->assertSame(
                'Invalid value for "with" parameter at index 0: expected relationship path string.',
                $exception->getMessage()
            );
        }
    }

    public function testFindFirstWithActionUsesRequestedRelationshipSubset(): void
    {
        $controller = $this->createController(['with' => 'Author.Profile']);
        $controller->setWith(new Collection(['Author.Profile', 'Comments'], false));
        $controller->findFirstWithResult = $this->createStub(ModelInterface::class);
        $controller->exposedData = [['id' => 3]];

        $controller->findFirstWithAction();

        $this->assertTrue($controller->findFirstWithCalled);
        $this->assertSame(['with' => ['Author.Profile'], 'find' => null], $controller->findFirstWithArguments);
        $this->assertSame(['id' => 3], $controller->view->getVar(FindActionControllerDouble::REST_VIEW_DATA));
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

    public function testFindActionCountRequestIgnoresDisabledMapEntries(): void
    {
        $controller = $this->createController([
            'count' => [
                FindActionControllerDouble::REST_VIEW_COUNT => 'off',
                FindActionControllerDouble::COUNT_RESPONSE_BUCKET_TOTAL => 0,
                FindActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT => 'yes',
            ],
        ], [9], [
            FindActionControllerDouble::REST_VIEW_COUNT,
            FindActionControllerDouble::COUNT_RESPONSE_BUCKET_TOTAL,
            FindActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT,
        ]);

        $controller->findAction();

        $this->assertNull($controller->view->getVar(FindActionControllerDouble::REST_VIEW_COUNT));
        $this->assertNull($controller->view->getVar(FindActionControllerDouble::COUNT_RESPONSE_BUCKET_TOTAL));
        $this->assertSame(9, $controller->view->getVar(FindActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT));
        $this->assertSame([[]], $controller->countFinds);
    }

    public function testFindActionRejectsInvalidCountParameterType(): void
    {
        $controller = $this->createController(['count' => new \stdClass()], [7]);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage(
            'Invalid type for "count" parameter: expected null, bool, string, or array, got object.'
        );

        $controller->findAction();
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

    public function testFindActionCountFieldPolicyNormalizesEnabledMapValues(): void
    {
        $controller = $this->createController([
            'count' => 'totalCount',
        ], [9]);
        $controller->setFindActionCountFields(new Collection([
            FindActionControllerDouble::REST_VIEW_COUNT => 'off',
            FindActionControllerDouble::COUNT_RESPONSE_BUCKET_TOTAL => 0,
            FindActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT => 'yes',
        ], false));

        $controller->findAction();

        $this->assertNull($controller->view->getVar(FindActionControllerDouble::REST_VIEW_COUNT));
        $this->assertNull($controller->view->getVar(FindActionControllerDouble::COUNT_RESPONSE_BUCKET_TOTAL));
        $this->assertSame(9, $controller->view->getVar(FindActionControllerDouble::COUNT_RESPONSE_TOTAL_COUNT));
        $this->assertSame([[]], $controller->countFinds);
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
