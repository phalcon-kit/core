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

namespace PhalconKit\Tests\Unit\Mvc\Controller\Traits;

use LogicException;
use Phalcon\Db\Column;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Support\Collection;
use PhalconKit\Exception\HttpException;
use PhalconKit\Exception\LogicException as PhalconKitLogicException;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Mvc\Model\Interfaces\EagerLoadInterface;
use PhalconKit\Mvc\Controller\Restful;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\QueryModelDouble;
use PhalconKit\Tests\Unit\AbstractUnit;

class QueryStateTest extends AbstractUnit
{
    public function testNullableCollectionsCanBeInitializedSetAndMerged(): void
    {
        $controller = $this->newQueryController();

        $controller->initializeBind();
        $controller->initializeBindTypes();
        $controller->initializeColumn();
        $controller->initializeHaving();

        $this->assertNull($controller->getBind());
        $this->assertNull($controller->getBindTypes());
        $this->assertNull($controller->getColumn());
        $this->assertNull($controller->getHaving());

        $controller->mergeBind(new Collection(['id' => 123]));
        $controller->mergeBind(new Collection(['status' => 'active']));
        $controller->mergeBindTypes(new Collection(['id' => 1]));
        $controller->mergeColumn(new Collection(['id']));
        $controller->mergeHaving(new Collection(['total > 0']));

        $this->assertSame(['id' => 123, 'status' => 'active'], $controller->getBind()?->toArray());
        $this->assertSame(['id' => 1], $controller->getBindTypes()?->toArray());
        $this->assertSame(['id'], $controller->getColumn()?->toArray());
        $this->assertSame(['total > 0'], $controller->getHaving()?->toArray());
    }

    public function testQueryPolicySettersNormalizeArraysToCollections(): void
    {
        $cases = [
            ['setBind', 'getBind', ['id' => 123]],
            ['setBindTypes', 'getBindTypes', ['id' => Column::BIND_PARAM_INT]],
            ['setCacheConfig', 'getCacheConfig', ['lifetime' => 60]],
            ['setColumn', 'getColumn', ['id']],
            ['setConditions', 'getConditions', ['default' => '[FooModel].[deleted] <> 1']],
            ['setDistinct', 'getDistinct', ['id' => true]],
            ['setDynamicJoins', 'getDynamicJoins', [
                'UserNode' => [
                    QueryModelDouble::class,
                    '[FooModel].[id] = [UserNode].[fooId]',
                    'UserNode',
                    'left',
                ],
            ]],
            ['setFilterConditions', 'getFilterConditions', ['default' => '[FooModel].[deleted] <> 1']],
            ['setFind', 'getFind', [
                'conditions' => '[FooModel].[id] = :id:',
                'bind' => ['id' => 123],
            ]],
            ['setGroup', 'getGroup', ['status' => '[FooModel].[status]']],
            ['setHaving', 'getHaving', ['COUNT(id) > 0']],
            ['setIdentityConditions', 'getIdentityConditions', ['default' => '[FooModel].[id] = :id:']],
            ['setJoins', 'getJoins', [
                'UserNode' => [
                    QueryModelDouble::class,
                    '[FooModel].[id] = [UserNode].[fooId]',
                    'UserNode',
                    'left',
                ],
            ]],
            ['setOrder', 'getOrder', ['createdAt' => 'desc']],
            ['setPermissionConditions', 'getPermissionConditions', ['default' => '[FooModel].[createdBy] = :userId:']],
            ['setSearchConditions', 'getSearchConditions', ['default' => '[FooModel].[title] like :search:']],
            ['setSoftDeleteConditions', 'getSoftDeleteConditions', ['default' => '[FooModel].[deleted] <> 1']],
            ['setWith', 'getWith', ['Author.Profile']],
        ];

        foreach ($cases as [$setter, $getter, $policy]) {
            $controller = $this->newQueryController();
            $controller->{$setter}($policy);

            $this->assertInstanceOf(Collection::class, $controller->{$getter}(), $getter);
            $this->assertSame($policy, $controller->{$getter}()?->toArray(), $getter);
        }
    }

    public function testQueryPolicyMergeHelpersAcceptArrays(): void
    {
        $controller = $this->newQueryController();

        $controller->mergeBind(['id' => 123]);
        $controller->mergeBind(['status' => 'active']);
        $controller->mergeBindTypes(['id' => Column::BIND_PARAM_INT]);
        $controller->mergeCacheConfig(['lifetime' => 60]);
        $controller->mergeCacheConfig(['key' => 'cache-key']);
        $controller->mergeColumn(['id']);
        $controller->mergeConditions(['one' => 'a = 1']);
        $controller->mergeDistinct(['id' => true]);
        $controller->mergeDynamicJoins(['UserNode' => ['join definition']]);
        $controller->mergeGroup(['status' => '[FooModel].[status]']);
        $controller->mergeHaving(['COUNT(id) > 0']);
        $controller->mergeJoins(['UserNode' => ['join definition']]);
        $controller->mergeWith(['Author.Profile']);

        $this->assertSame(['id' => 123, 'status' => 'active'], $controller->getBind()?->toArray());
        $this->assertSame(['id' => Column::BIND_PARAM_INT], $controller->getBindTypes()?->toArray());
        $this->assertSame(['lifetime' => 60, 'key' => 'cache-key'], $controller->getCacheConfig()?->toArray());
        $this->assertSame(['id'], $controller->getColumn()?->toArray());
        $this->assertSame(['one' => 'a = 1'], $controller->getConditions()?->toArray());
        $this->assertSame(['id' => true], $controller->getDistinct()?->toArray());
        $this->assertSame(['UserNode' => ['join definition']], $controller->getDynamicJoins()?->toArray());
        $this->assertSame(['status' => '[FooModel].[status]'], $controller->getGroup()?->toArray());
        $this->assertSame(['COUNT(id) > 0'], $controller->getHaving()?->toArray());
        $this->assertSame(['UserNode' => ['join definition']], $controller->getJoins()?->toArray());
        $this->assertSame(['Author.Profile'], $controller->getWith()?->toArray());
    }

    public function testFieldCollectionsTrackPresenceAndMergeState(): void
    {
        $controller = $this->newQueryController();

        $controller->initializeExposeFields();
        $controller->initializeFilterFields();
        $controller->initializeMapFields();
        $controller->initializeOrderFields();
        $controller->initializeSaveFields();
        $controller->initializeSearchFields();

        $this->assertFalse($controller->hasExposeFields());
        $this->assertFalse($controller->hasFilterFields());
        $this->assertFalse($controller->hasMapFields());
        $this->assertFalse($controller->hasOrderFields());
        $this->assertFalse($controller->hasSaveFields());
        $this->assertFalse($controller->hasSearchFields());
        $this->assertNull($controller->getFilterFields());
        $this->assertNull($controller->getMapFields());
        $this->assertNull($controller->getOrderFields());
        $this->assertNull($controller->getSaveFields());
        $this->assertNull($controller->getSearchFields());

        $controller->mergeExposeFields(new Collection(['id']));
        $controller->mergeFilterFields(new Collection(['status']));
        $controller->mergeMapFields(new Collection(['publicName' => 'name']));
        $controller->mergeOrderFields(new Collection(['createdAt']));
        $controller->mergeSaveFields(new Collection(['title']));
        $controller->mergeSearchFields(new Collection(['body']));

        $this->assertTrue($controller->hasExposeFields());
        $this->assertTrue($controller->hasFilterFields());
        $this->assertTrue($controller->hasMapFields());
        $this->assertTrue($controller->hasOrderFields());
        $this->assertTrue($controller->hasSaveFields());
        $this->assertTrue($controller->hasSearchFields());
        $this->assertSame(['id'], $controller->getExposeFields()?->toArray());
        $this->assertSame(['status'], $controller->getFilterFields()?->toArray());
        $this->assertSame(['publicName' => 'name'], $controller->getMapFields()?->toArray());
        $this->assertSame(['createdAt'], $controller->getOrderFields()?->toArray());
        $this->assertSame(['title'], $controller->getSaveFields()?->toArray());
        $this->assertSame(['body'], $controller->getSearchFields()?->toArray());
    }

    public function testDistinctInitializesFromCommaSeparatedString(): void
    {
        $controller = $this->newQueryController([
            'distinct' => 'id, title,year',
        ]);

        $controller->initializeDistinct();

        $this->assertSame([
            'id' => true,
            'title' => true,
            'year' => true,
        ], $controller->getDistinct()?->toArray());
    }

    public function testDistinctInitializesToNullWhenMissing(): void
    {
        $controller = $this->newQueryController();

        $controller->initializeDistinct();

        $this->assertNull($controller->getDistinct());
    }

    public function testGroupInitializesExplicitFieldsWithModelAlias(): void
    {
        $controller = $this->newQueryController([
            'group' => 'id, title',
        ]);

        $controller->initializeGroup();

        $this->assertSame([
            'id' => '[FooModel].[id]',
            'title' => '[FooModel].[title]',
        ], $controller->getGroup()?->toArray());
    }

    public function testGroupFallsBackToPrimaryKeyWhenJoinsExist(): void
    {
        $controller = $this->newQueryController();
        $controller->setJoins(new Collection(['FooJoin' => ['join definition']], false));

        $controller->initializeGroup();

        $this->assertSame([
            'id' => '[FooModel].[id]',
        ], $controller->getGroup()?->toArray());
    }

    public function testCacheConfigUsesLifetimeModelIdentityAndParams(): void
    {
        $controller = $this->newQueryController([
            'lifetime' => 120,
            'filter' => 'active',
        ]);

        $controller->initializeCacheConfig();

        $this->assertSame(120, $controller->getCacheLifetime());
        $this->assertStringStartsWith('_FooModel-120-42-', (string)$controller->getCacheKey());
        $this->assertStringEndsWith('_', (string)$controller->getCacheKey());
        $this->assertSame([
            'lifetime' => 120,
            'key' => $controller->getCacheKey(),
        ], $controller->getCacheConfig()?->toArray());
    }

    public function testLimitAllowsUnlimitedAndRejectsInvalidValues(): void
    {
        $controller = $this->newQueryController();
        $controller->setMaxLimit(100);

        $controller->setLimit(-1);
        $this->assertNull($controller->getLimit());

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('must be higher or equal to -1');

        $controller->setLimit(-2);
    }

    public function testLimitRejectsValuesAboveTheConfiguredMaximum(): void
    {
        $controller = $this->newQueryController();
        $controller->setMaxLimit(5);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('must be lower than the maximum limit');

        $controller->setLimit(6);
    }

    public function testOffsetDefaultsToZeroAndRejectsNegativeValues(): void
    {
        $controller = $this->newQueryController([
            'offset' => 7,
        ]);

        $controller->initializeOffset();

        $this->assertSame(7, $controller->getOffset());
        $this->assertSame(0, $controller->defaultOffset());

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('must be higher than or equal to 0');

        $controller->setOffset(-1);
    }

    public function testOrderInitializesStringAndArrayDefinitions(): void
    {
        $controller = $this->newQueryController([
            'order' => 'title desc, year, createdAt invalid',
        ]);

        $controller->initializeOrder();

        $this->assertSame([
            'title' => '[FooModel].[title] desc',
            'year' => '[FooModel].[year] asc',
            'createdAt' => '[FooModel].[createdAt] asc',
        ], $controller->getOrder()?->toArray());

        $controller = $this->newQueryController([
            'order' => [
                'name' => 'DESC',
                'id' => 'sideways',
            ],
        ]);

        $controller->initializeOrder();

        $this->assertSame([
            'name' => '[FooModel].[name] desc',
            'id' => '[FooModel].[id] asc',
        ], $controller->getOrder()?->toArray());
    }

    public function testOrderFieldsAllowConfiguredStringAndArrayDefinitions(): void
    {
        $controller = $this->newQueryController([
            'order' => 'title desc, ownerEmail asc',
        ]);
        $controller->setOrderFields(new Collection([
            'title',
            'ownerEmail' => 'Owner.email',
        ]));

        $controller->initializeOrder();

        $this->assertSame([
            'title' => '[FooModel].[title] desc',
            'ownerEmail' => '[Owner].[email] asc',
        ], $controller->getOrder()?->toArray());

        $controller = $this->newQueryController([
            'order' => [
                'createdAt' => 'DESC',
                'Owner.email' => 'asc',
            ],
        ]);
        $controller->setOrderFields(new Collection([
            'createdAt' => true,
            'Owner.email',
        ]));

        $controller->initializeOrder();

        $this->assertSame([
            'createdAt' => '[FooModel].[createdAt] desc',
            'Owner.email' => '[Owner].[email] asc',
        ], $controller->getOrder()?->toArray());
    }

    public function testOrderRejectsUnauthorizedConfiguredField(): void
    {
        $controller = $this->newQueryController([
            'order' => 'title desc, privateNote asc',
        ]);
        $controller->setOrderFields(new Collection(['title']));

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized order field "privateNote".');

        $controller->initializeOrder();
    }

    public function testOrderAppliesConfiguredPolicyToDefaultOrder(): void
    {
        $controller = $this->newQueryController();
        $controller->unitDefaultOrder = ['ownerEmail' => 'desc'];
        $controller->setOrderFields(new Collection([
            'ownerEmail' => 'Owner.email',
        ]));

        $controller->initializeOrder();

        $this->assertSame([
            'ownerEmail' => '[Owner].[email] desc',
        ], $controller->getOrder()?->toArray());
    }

    public function testOrderRejectsInvalidRootAndElementDefinitions(): void
    {
        $controller = $this->newQueryController([
            'order' => new \stdClass(),
        ]);

        try {
            $controller->initializeOrder();
            $this->fail('Expected invalid root order type to throw.');
        }
        catch (HttpException $exception) {
            $this->assertStringContainsString('Invalid type for "order" parameter', $exception->getMessage());
        }

        $controller = $this->newQueryController([
            'order' => [
                ['title', 'desc', 'extra'],
            ],
        ]);

        try {
            $controller->initializeOrder();
            $this->fail('Expected invalid order element to throw.');
        }
        catch (HttpException $exception) {
            $this->assertStringContainsString('expected [field, direction] with at most 2 elements', $exception->getMessage());
        }
    }

    public function testGroupAndOrderSkipEmptyBranches(): void
    {
        $controller = $this->newQueryController([
            'group' => ', id',
        ]);
        $controller->initializeGroup();
        $this->assertSame([
            'id' => '[FooModel].[id]',
        ], $controller->getGroup()?->toArray());

        $controller = $this->newQueryController();
        $controller->initializeOrder();
        $this->assertNull($controller->getOrder());

        $controller = $this->newQueryController([
            'order' => [''],
        ]);
        $controller->initializeOrder();
        $this->assertSame([], $controller->getOrder()?->toArray());

        $controller = $this->newQueryController([
            'order' => [
                ['', 'desc'],
                'id desc',
            ],
        ]);
        $controller->initializeOrder();
        $this->assertSame([
            'id' => '[FooModel].[id] desc',
        ], $controller->getOrder()?->toArray());

        $controller = $this->newQueryController([
            'order' => [
                '' => 'desc',
                'id' => 'asc',
            ],
        ]);
        $controller->initializeOrder();
        $this->assertSame([
            'id' => '[FooModel].[id] asc',
        ], $controller->getOrder()?->toArray());

        $controller = $this->newQueryController([
            'order' => [123],
        ]);

        try {
            $controller->initializeOrder();
            $this->fail('Expected invalid order element type to throw.');
        }
        catch (HttpException $exception) {
            $this->assertStringContainsString('Invalid order element at index 0', $exception->getMessage());
        }
    }

    public function testPrepareCollectionToCompilePreservesKeysAndDropsNulls(): void
    {
        $controller = $this->newQueryController();

        $compiled = $controller->prepareCollectionToCompile(new Collection([
            'conditions' => new Collection([
                0 => 'id = :id:',
                1 => null,
                3 => 'status = :status:',
            ], false),
            'bind' => new Collection([
                'id' => 123,
                'status' => 'active',
                9 => 'positional',
                'ignored' => null,
            ], false),
            'keepFalse' => false,
            'keepZero' => 0,
            'dropNull' => null,
        ], false));

        $this->assertSame([
            'conditions' => [
                0 => 'id = :id:',
                3 => 'status = :status:',
            ],
            'bind' => [
                'id' => 123,
                'status' => 'active',
                9 => 'positional',
            ],
            'keepFalse' => false,
            'keepZero' => 0,
        ], $compiled);

        $compiled = $controller->prepareCollectionToCompile(new Collection([
            'bind' => new Collection([
                9 => 'positional',
            ], false),
        ], false));

        $this->assertSame([
            'bind' => [
                9 => 'positional',
            ],
        ], $compiled);
    }

    public function testPrepareFindCompilesCollectionsAndJoinPayloads(): void
    {
        $controller = $this->newQueryController();
        $controller->setFind(new Collection([
            'conditions' => new Collection([
                'id = :id:',
            ], false),
            'bind' => new Collection([
                'id' => 7,
            ], false),
            'bindTypes' => new Collection([
                'id' => Column::BIND_PARAM_INT,
            ], false),
            'joins' => new Collection([
                [
                    'JoinModel',
                    '[FooModel].[joinId] = [JoinAlias].[id]',
                    'JoinAlias',
                    [
                        'conditions' => '[JoinAlias].[status] = :joinStatus:',
                        'bind' => ['joinStatus' => 'active'],
                        'bindTypes' => ['joinStatus' => Column::BIND_PARAM_STR],
                    ],
                ],
            ], false),
        ], false));

        $find = $controller->prepareFind();

        $this->assertSame('(id = :id:)', $find['conditions']);
        $this->assertSame([
            'id' => 7,
            'joinStatus' => 'active',
        ], $find['bind']);
        $this->assertSame([
            'id' => Column::BIND_PARAM_INT,
            'joinStatus' => Column::BIND_PARAM_STR,
        ], $find['bindTypes']);
        $this->assertSame([
            [
                'JoinModel',
                '([FooModel].[joinId] = [JoinAlias].[id]) AND (([JoinAlias].[status] = :joinStatus:))',
                'JoinAlias',
            ],
        ], $find['joins']);
    }

    public function testCompilerCompilesNestedNamedConditionPayloads(): void
    {
        $controller = $this->newQueryController();

        $compiled = $controller->compileFind([
            'conditions' => [
                'permission' => [
                    'default' => [
                        'createdBy = :createdBy:',
                        ['createdBy' => 42],
                        ['createdBy' => Column::BIND_PARAM_INT],
                    ],
                ],
                'search' => [
                    'default' => [
                        'title LIKE :search:',
                        ['search' => '%foo%'],
                        ['search' => Column::BIND_PARAM_STR],
                    ],
                ],
                null,
                '',
            ],
            'group' => ['id', 'id', 'name'],
            'order' => 'id desc',
            'limit' => '25',
        ]);

        $this->assertSame('((createdBy = :createdBy:)) AND ((title LIKE :search:))', $compiled['conditions']);
        $this->assertSame([
            'createdBy' => 42,
            'search' => '%foo%',
        ], $compiled['bind']);
        $this->assertSame([
            'createdBy' => Column::BIND_PARAM_INT,
            'search' => Column::BIND_PARAM_STR,
        ], $compiled['bindTypes']);
        $this->assertSame('id, name', $compiled['group']);
        $this->assertSame('id desc', $compiled['order']);
        $this->assertSame(25, $compiled['limit']);
    }

    public function testCompilerMergesDefinitionsAndRejectsConflicts(): void
    {
        $controller = $this->newQueryController();

        $merged = $controller->mergeCompiledFind(
            [
                'conditions' => ['active = 1'],
                'bind' => ['id' => 7],
                'bindTypes' => ['id' => Column::BIND_PARAM_INT],
                'for_update' => false,
            ],
            [
                'conditions' => ['active = 1', 'deleted = 0'],
                'bind' => ['id' => 7],
                'bindTypes' => ['id' => Column::BIND_PARAM_INT],
                'shared_lock' => true,
                'order' => ['id desc', 'id desc'],
            ]
        );

        $this->assertSame('(active = 1) AND (deleted = 0)', $merged['conditions']);
        $this->assertSame(['id' => 7], $merged['bind']);
        $this->assertSame(['id' => Column::BIND_PARAM_INT], $merged['bindTypes']);
        $this->assertFalse($merged['for_update']);
        $this->assertTrue($merged['shared_lock']);
        $this->assertSame('id desc', $merged['order']);

        foreach ([
            'different limit' => [
                'Cannot merge find definitions with different limit.',
                fn() => $controller->mergeCompiledFind(['limit' => 1], ['limit' => 2]),
            ],
            'bind collision' => [
                'bind key collision on "id"',
                fn() => $controller->mergeCompiledFind(['bind' => ['id' => 1]], ['bind' => ['id' => 2]]),
            ],
            'unknown key collision' => [
                'key collision on "custom"',
                fn() => $controller->mergeCompiledFind(['custom' => 'a'], ['custom' => 'b']),
            ],
            'integer-like key' => [
                'integer-like keys are not allowed',
                fn() => $controller->mergeCompiledFind(['01' => 'invalid']),
            ],
        ] as $case) {
            [$message, $callback] = $case;

            try {
                $callback();
                $this->fail('Expected compiler conflict for ' . $message . '.');
            }
            catch (LogicException $exception) {
                $this->assertStringContainsString($message, $exception->getMessage());
            }
        }
    }

    public function testJoinNormalizationMergesPayloadIntoJoinSqlAndBinds(): void
    {
        $controller = $this->newQueryController();

        $normalized = $controller->exposeNormalizeJoins([
            [
                'FooJoinModel',
                '[FooModel].[fooId] = [FooAlias].[id]',
                'FooAlias',
                'left',
                [
                    [
                        'conditions' => '[FooAlias].[status] = :joinStatus:',
                        'bind' => ['joinStatus' => 'active'],
                        'bindTypes' => ['joinStatus' => Column::BIND_PARAM_STR],
                    ],
                    [
                        '[FooAlias].[deleted] = :joinDeleted:',
                        ['joinDeleted' => 0],
                        ['joinDeleted' => Column::BIND_PARAM_INT],
                    ],
                ],
            ],
        ]);

        $this->assertSame([
            [
                'FooJoinModel',
                '([FooModel].[fooId] = [FooAlias].[id]) AND (([FooAlias].[status] = :joinStatus:) AND ([FooAlias].[deleted] = :joinDeleted:))',
                'FooAlias',
                'left',
            ],
        ], $normalized['joins']);
        $this->assertSame([
            'joinStatus' => 'active',
            'joinDeleted' => 0,
        ], $normalized['bind']);
        $this->assertSame([
            'joinStatus' => Column::BIND_PARAM_STR,
            'joinDeleted' => Column::BIND_PARAM_INT,
        ], $normalized['bindTypes']);

        $normalized = $controller->exposeNormalizeJoins([
            'RecordUserStatus[a]' => [
                'RecordUserStatusModel',
                '[FooModel].[id] = [RecordUserStatus].[recordId]',
                'RecordUserStatus[a]',
                'left',
                [
                    'conditions' => '[RecordUserStatus].[deleted] = :deleted:',
                    'bind' => ['deleted' => 0],
                    'bindTypes' => ['deleted' => Column::BIND_PARAM_INT],
                ],
            ],
        ]);

        $this->assertSame([
            [
                'RecordUserStatusModel',
                '([FooModel].[id] = [RecordUserStatus].[recordId]) AND (([RecordUserStatus].[deleted] = :deleted:))',
                'RecordUserStatus[a]',
                'left',
            ],
        ], $normalized['joins']);
        $this->assertSame(['deleted' => 0], $normalized['bind']);
    }

    public function testJoinNormalizationRejectsInvalidDefinitions(): void
    {
        $controller = $this->newQueryController();

        foreach ([
            'missing tuple members' => [
                'Invalid join definition at index 0.',
                [['FooJoinModel']],
            ],
            'non-string ON' => [
                'Join ON must be a SQL string at index 0.',
                [['FooJoinModel', ['invalid'], 'FooAlias']],
            ],
            'non-string type' => [
                'Join type must be a string at index 0.',
                [['FooJoinModel', '1 = 1', 'FooAlias', 123]],
            ],
            'non-array bind' => [
                'Join payload bind must be an array at join index 0, block 0.',
                [['FooJoinModel', '1 = 1', 'FooAlias', ['bind' => 'invalid']]],
            ],
        ] as $case) {
            [$message, $joins] = $case;

            try {
                $controller->exposeNormalizeJoins($joins);
                $this->fail('Expected invalid join definition for ' . $message . '.');
            }
            catch (LogicException $exception) {
                $this->assertSame($message, $exception->getMessage());
            }
        }
    }

    public function testDynamicJoinsBuildsRequestedRelationshipAliases(): void
    {
        $controller = $this->newQueryController([
            'filters' => [
                [
                    'field' => 'Author.Profile.email',
                    'operator' => 'contains',
                    'value' => 'foo',
                ],
            ],
        ]);

        $controller->setDynamicJoins(new Collection([
            'Author' => [
                'AuthorModel',
                '[FooModel].[authorId] = [Author].[id]',
                'Author',
                'left',
            ],
            'Author.Profile' => [
                'ProfileModel',
                '[Author].[profileId] = [Author.Profile].[id]',
                'Author.Profile',
                'inner',
            ],
        ], false));

        $joins = $controller->getJoins()?->toArray();

        $this->assertArrayHasKey('Author', $joins);
        $this->assertArrayHasKey('Author.Profile', $joins);
        $this->assertSame('AuthorModel', $joins['Author'][0]);
        $this->assertSame('ProfileModel', $joins['Author.Profile'][0]);
        $this->assertStringStartsWith('_', $joins['Author'][2]);
        $this->assertStringStartsWith('_', $joins['Author.Profile'][2]);
        $this->assertStringContainsString('[' . $joins['Author'][2] . '].[profileId]', $joins['Author.Profile'][1]);
        $this->assertStringContainsString('[' . $joins['Author.Profile'][2] . '].[id]', $joins['Author.Profile'][1]);
    }

    public function testDynamicJoinsAcceptTwoPartDefinitionsForBracketedRelationshipAliases(): void
    {
        $controller = $this->newQueryController([
            'filters' => [
                [
                    'field' => 'projectId',
                    'operator' => 'equals',
                    'value' => 98,
                ],
                [
                    [
                        [
                            'field' => 'RecordUserStatus[a].userId',
                            'operator' => 'equals',
                            'value' => [14],
                        ],
                        [
                            'field' => 'RecordUserStatus[a].type',
                            'operator' => 'equals',
                            'value' => ['final'],
                        ],
                    ],
                ],
            ],
            'joins' => [],
        ]);

        $controller->setDynamicJoins(new Collection([
            'RecordUserStatus' => [
                'RecordUserStatusModel',
                '[FooModel].[id] = [RecordUserStatus].[recordId] and [RecordUserStatus].[deleted] <> 1',
            ],
        ], false));

        $joins = $controller->getJoins()?->toArray();

        $this->assertArrayHasKey('RecordUserStatus[a]', $joins);
        $join = $joins['RecordUserStatus[a]'];
        $this->assertSame('RecordUserStatusModel', $join[0]);
        $this->assertStringStartsWith('_', $join[2]);
        $this->assertSame('left', $join[3]);
        $this->assertSame([], $join[4]);
        $this->assertStringContainsString('[FooModel].[id]', $join[1]);
        $this->assertStringContainsString('[' . $join[2] . '].[recordId]', $join[1]);
        $this->assertStringContainsString('[' . $join[2] . '].[deleted] <> 1', $join[1]);
    }

    public function testDynamicJoinsAcceptLegacyAssociativeDefinitions(): void
    {
        $controller = $this->newQueryController([
            'filters' => [
                [
                    'field' => 'Article[a].title',
                    'operator' => 'contains',
                    'value' => 'foo',
                ],
            ],
        ]);

        $controller->setDynamicJoins(new Collection([
            'Article' => [
                'ArticleModel' => '[FooModel].[id] = [Article].[recordId] and [Article].[deleted] <> 1',
            ],
        ], false));

        $joins = $controller->getJoins()?->toArray();

        $this->assertArrayHasKey('Article[a]', $joins);
        $join = $joins['Article[a]'];
        $this->assertSame('ArticleModel', $join[0]);
        $this->assertStringStartsWith('_', $join[2]);
        $this->assertSame('left', $join[3]);
        $this->assertStringContainsString('[' . $join[2] . '].[recordId]', $join[1]);
        $this->assertStringContainsString('[' . $join[2] . '].[deleted] <> 1', $join[1]);
    }

    public function testDynamicJoinsRejectInvalidDefinitions(): void
    {
        foreach ([
            'not an array' => 'AuthorModel',
            'missing condition' => ['AuthorModel'],
        ] as $case => $definition) {
            $controller = $this->newQueryController([
                'filters' => [
                    [
                        'field' => 'Author.email',
                        'operator' => 'contains',
                        'value' => 'foo',
                    ],
                ],
            ]);

            try {
                $controller->setDynamicJoins(new Collection([
                    'Author' => $definition,
                ], false));
                $this->fail('Expected invalid dynamic join definition for ' . $case . '.');
            }
            catch (LogicException $exception) {
                $this->assertSame('Invalid dynamic join definition for `Author`.', $exception->getMessage());
            }
        }
    }

    public function testGetJoinsDefinitionFromFieldReturnsDeepestJoinFirst(): void
    {
        $controller = $this->newQueryController();
        $controller->setJoins(new Collection([
            'Author' => [
                'AuthorModel',
                '[FooModel].[authorId] = [Author].[id]',
                'Author',
                'left',
            ],
            'Author.Profile' => [
                'ProfileModel',
                '[Author].[profileId] = [Author.Profile].[id]',
                'Author.Profile',
                'inner',
            ],
        ], false));

        $this->assertSame([], $controller->getJoinsDefinitionFromField('title'));
        $this->assertSame([
            [
                'ProfileModel',
                '[Author].[profileId] = [Author.Profile].[id]',
                'Author.Profile',
                'inner',
            ],
            [
                'AuthorModel',
                '[FooModel].[authorId] = [Author].[id]',
                'Author',
                'left',
            ],
        ], $controller->getJoinsDefinitionFromField('Author.Profile.email'));
    }

    public function testConditionBuildersCreateBindablePayloads(): void
    {
        $controller = $this->newQueryController([
            'search' => 'alpha beta alpha',
        ]);

        $identity = $controller->buildIdentityConditionFromData(['uuid' => 'abc-123'], ['uuid']);
        $this->assertIsArray($identity);
        $this->assertStringContainsString('[FooModel].[uuid] = :_identity_', $identity[0]);
        $this->assertSame(['abc-123'], array_values($identity[1]));
        $this->assertSame([Column::BIND_PARAM_STR], array_values($identity[2]));
        $this->assertNull($controller->buildIdentityConditionFromData([], ['uuid']));

        $softDelete = $controller->buildDefaultSoftDeleteCondition();
        $this->assertIsArray($softDelete);
        $this->assertStringContainsString('[FooModel].[deleted] = :_deleted_', $softDelete[0]);
        $this->assertSame([0], array_values($softDelete[1]));
        $this->assertSame([Column::BIND_PARAM_INT], array_values($softDelete[2]));

        $controller->identity = new class {
            public function getUserId(): int
            {
                return 42;
            }

            public function hasRole(array|string $roles): bool
            {
                return true;
            }
        };
        $this->assertNull($controller->buildDefaultPermissionCondition());

        $controller->identity = new class {
            public function getUserId(): int
            {
                return 42;
            }

            public function hasRole(array|string $roles): bool
            {
                return false;
            }
        };
        $permission = $controller->buildOwnerCondition(42, ['createdBy', '', 'updatedBy']);
        $this->assertIsArray($permission);
        $this->assertStringContainsString('[FooModel].[createdBy] = :_permission_', $permission[0]);
        $this->assertStringContainsString(' OR ', $permission[0]);
        $this->assertStringContainsString('[FooModel].[updatedBy] = :_permission_', $permission[0]);
        $this->assertSame([42, 42], array_values($permission[1]));
        $this->assertSame([Column::BIND_PARAM_INT, Column::BIND_PARAM_INT], array_values($permission[2]));
        $this->assertNull($controller->buildOwnerCondition(42, ['', 123]));

        $controller->setSearchFields(new Collection([
            'title',
            'body',
            'disabled' => false,
            'archived' => 'off',
            'hidden' => 'false',
            'summary' => 'yes',
        ], false));

        $search = $controller->buildDefaultSearchCondition();
        $this->assertIsArray($search);
        $this->assertStringContainsString('[FooModel].[title] LIKE :_search_', $search[0]);
        $this->assertStringContainsString('[FooModel].[body] LIKE :_search_', $search[0]);
        $this->assertStringContainsString('[FooModel].[summary] LIKE :_search_', $search[0]);
        $this->assertStringNotContainsString('[FooModel].[disabled]', $search[0]);
        $this->assertStringNotContainsString('[FooModel].[archived]', $search[0]);
        $this->assertStringNotContainsString('[FooModel].[hidden]', $search[0]);
        $this->assertSame([
            '%alpha%' => 3,
            '%beta%' => 3,
        ], array_count_values($search[1]));
        $this->assertSame([
            Column::BIND_PARAM_STR,
        ], array_values(array_unique($search[2])));
    }

    public function testInitializeFieldsConditionsWithJoinsAndDynamicJoinsComposition(): void
    {
        $controller = $this->newQueryController([
            'id' => 7,
        ]);

        $controller->initializeFields();

        $this->assertFalse($controller->hasExposeFields());
        $this->assertFalse($controller->hasFilterFields());
        $this->assertFalse($controller->hasMapFields());
        $this->assertFalse($controller->hasSaveFields());
        $this->assertFalse($controller->hasSearchFields());

        $controller->initializeConditions();
        $conditions = $controller->getConditions();

        $this->assertInstanceOf(Collection::class, $conditions);
        $this->assertInstanceOf(Collection::class, $conditions->get('permission'));
        $this->assertInstanceOf(Collection::class, $conditions->get('softDelete'));
        $this->assertInstanceOf(Collection::class, $conditions->get('identity'));
        $this->assertInstanceOf(Collection::class, $conditions->get('filter'));
        $this->assertInstanceOf(Collection::class, $conditions->get('search'));
        $this->assertIsArray($conditions->get('permission')->get('default'));
        $this->assertIsArray($conditions->get('softDelete')->get('default'));
        $this->assertIsArray($conditions->get('identity')->get('default'));
        $this->assertNull($conditions->get('filter')->get('default'));
        $this->assertNull($conditions->get('search')->get('default'));

        $controller->initializeWith();
        $this->assertNull($controller->getWith());
        $controller->mergeWith(new Collection(['UserEntity']));
        $controller->mergeWith(new Collection(['RoleList']));
        $this->assertSame(['UserEntity', 'RoleList'], $controller->getWith()?->toArray());

        $controller->initializeJoins();
        $this->assertNull($controller->getJoins());
        $controller->mergeJoins(new Collection([
            'UserEntity' => ['UserModel', '1 = 1', 'UserEntity'],
        ], false));
        $controller->mergeJoins(new Collection([
            'RoleList' => ['RoleModel', '1 = 1', 'RoleList'],
        ], false));
        $this->assertSame([
            'UserEntity' => ['UserModel', '1 = 1', 'UserEntity'],
            'RoleList' => ['RoleModel', '1 = 1', 'RoleList'],
        ], $controller->getJoins()?->toArray());

        $controller->initializeDynamicJoins();
        $this->assertNull($controller->getDynamicJoins());
        $controller->mergeDynamicJoins(new Collection([
            'UserEntity' => ['UserModel', '1 = 1', 'UserEntity'],
        ], false));
        $controller->mergeDynamicJoins(new Collection([
            'RoleList' => ['RoleModel', '1 = 1', 'RoleList'],
        ], false));
        $this->assertSame([
            'UserEntity' => ['UserModel', '1 = 1', 'UserEntity'],
            'RoleList' => ['RoleModel', '1 = 1', 'RoleList'],
        ], $controller->getDynamicJoins()?->toArray());

        $controller->setDefaultOrder(['id' => 'desc']);
        $this->assertSame(['id' => 'desc'], $controller->getDefaultOrder());
        $controller->initializeDefaultOrder();
        $this->assertNull($controller->getDefaultOrder());
    }

    public function testInitializeQueryComposesPermissionFilterAndSearchConditions(): void
    {
        $controller = $this->newQueryController([
            'id' => 7,
            'filters' => [
                [
                    'field' => 'status',
                    'operator' => '=',
                    'value' => 'active',
                ],
            ],
            'search' => 'alpha',
        ]);

        $eventsManager = new \Phalcon\Events\Manager();
        $eventsManager->attach(
            'rest',
            static function (\Phalcon\Events\Event $event, Restful $controller): void {
                if ($event->getType() !== 'afterInitializeFields') {
                    return;
                }

                $controller->setFilterFields(new Collection(['status'], false));
                $controller->setSearchFields(new Collection(['title'], false));
            }
        );
        $controller->setEventsManager($eventsManager);

        $controller->initializeQuery();
        $find = $controller->prepareFind();

        $this->assertStringContainsString('[FooModel].[createdBy] = :_permission_', $find['conditions']);
        $this->assertStringContainsString('[FooModel].[deleted] = :_deleted_', $find['conditions']);
        $this->assertStringContainsString('[FooModel].[id] = :_identity_', $find['conditions']);
        $this->assertStringContainsString('[FooModel].[status] = :_', $find['conditions']);
        $this->assertStringContainsString('[FooModel].[title] LIKE :_search_', $find['conditions']);
        $this->assertContains(42, $find['bind']);
        $this->assertContains(0, $find['bind']);
        $this->assertContains(7, $find['bind']);
        $this->assertContains('active', $find['bind']);
        $this->assertContains('%alpha%', $find['bind']);
        $this->assertContains(Column::BIND_PARAM_INT, $find['bindTypes']);
        $this->assertContains(Column::BIND_PARAM_STR, $find['bindTypes']);
    }

    public function testFilterOperatorSemanticsAndFieldPolicy(): void
    {
        $controller = $this->newQueryController();

        $this->assertSame('=', $controller->normalizeFilterOperator(' equal to '));
        $this->assertSame('>', $controller->normalizeFilterOperator('greater then'));
        $this->assertSame('does not start with', $controller->normalizeFilterOperator('does not starts with'));
        $this->assertSame('regexp', $controller->normalizeFilterOperator('match'));
        $this->assertSame('is not empty', $controller->normalizeFilterOperator('not empty'));
        $this->assertSame('', $controller->normalizeFilterOperator('roughly equals'));

        $this->assertTrue($controller->exposeIsNegativeOperator('not in'));
        $this->assertTrue($controller->exposeIsNegativeOperator('!='));
        $this->assertFalse($controller->exposeIsNegativeOperator('contains'));

        $this->assertTrue($controller->exposeIsTextOperator('does not contain'));
        $this->assertFalse($controller->exposeIsTextOperator('!='));

        $this->assertTrue($controller->exposeIsNegativeTextOperator('does not contain word'));
        $this->assertFalse($controller->exposeIsNegativeTextOperator('not in'));

        $this->assertTrue($controller->exposeIsNoValueOperator('is null'));
        $this->assertTrue($controller->exposeIsNoValueOperator('is not empty'));
        $this->assertFalse($controller->exposeIsNoValueOperator('is not empty', extended: false));
        $this->assertFalse($controller->exposeIsNoValueOperator('=', raw: false));

        $this->assertTrue($controller->isFilterAllowed('status', ['status']));
        $this->assertTrue($controller->isFilterAllowed('status', ['status' => true]));
        $this->assertTrue($controller->isFilterAllowed('status', ['status' => 'yes']));
        $this->assertFalse($controller->isFilterAllowed('status', ['status' => 'off']));
        $this->assertFalse($controller->isFilterAllowed('status', ['status' => 'false']));
        $this->assertTrue($controller->isFilterAllowed('Author[primary].email', ['Author.email']));
        $this->assertTrue($controller->isFilterAllowed('Author[primary].email', ['Author.email' => true]));
        $this->assertTrue($controller->isFilterAllowed('Author[primary].email', ['Author.email' => 'on']));
        $this->assertFalse($controller->isFilterAllowed('Author[primary].email', ['Author.email' => '0']));
        $this->assertTrue($controller->isFilterAllowed('status', null));
        $this->assertFalse($controller->isFilterAllowed('status', []));
        $this->assertFalse($controller->isJoinFilterAllowed('Author[primary].email', ['Author.name']));
    }

    public function testFilterCompilerPreservesNullPolicyAsUnrestrictedAndEmptyPolicyAsClosed(): void
    {
        $filters = [
            [
                'field' => 'status',
                'operator' => '=',
                'value' => 'active',
            ],
        ];

        $controller = $this->newQueryController([
            'filters' => $filters,
        ]);
        $controller->initializeFilterFields();

        $compiled = $controller->defaultFilterCondition();

        $this->assertIsArray($compiled);
        $this->assertStringContainsString('[FooModel].[status] = :_', $compiled[0]);
        $this->assertSame(['active'], array_values($compiled[1]));
        $this->assertSame([Column::BIND_PARAM_STR], array_values($compiled[2]));

        $controller = $this->newQueryController([
            'filters' => $filters,
        ]);
        $controller->setFilterFields(new Collection([], false));

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized filter field "status".');

        $controller->defaultFilterCondition();
    }

    public function testFilterCompilerNormalizesEnabledMapFieldPolicyValues(): void
    {
        $filters = [
            [
                'field' => 'status',
                'operator' => '=',
                'value' => 'active',
            ],
        ];

        $controller = $this->newQueryController([
            'filters' => $filters,
        ]);
        $controller->setFilterFields(new Collection([
            'status' => 'yes',
        ], false));

        $compiled = $controller->defaultFilterCondition();

        $this->assertIsArray($compiled);
        $this->assertStringContainsString('[FooModel].[status] = :_', $compiled[0]);
        $this->assertSame(['active'], array_values($compiled[1]));

        $controller = $this->newQueryController([
            'filters' => $filters,
        ]);
        $controller->setFilterFields(new Collection([
            'status' => 'off',
        ], false));

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized filter field "status".');

        $controller->defaultFilterCondition();
    }

    public function testFilterCompilerBuildsSelfConditionsAndValidatesInput(): void
    {
        $controller = $this->newQueryController();

        $compiled = $controller->defaultFilterCondition([
            [
                'field' => 'status',
                'operator' => 'contains',
                'value' => 'active',
            ],
            [
                'field' => 'age',
                'operator' => 'between',
                'value' => [40, 18],
            ],
            [
                'field' => 'deleted',
                'operator' => 'is false',
            ],
        ], ['status', 'age', 'deleted']);

        $this->assertIsArray($compiled);
        $this->assertStringContainsString('[FooModel].[status] like :_', $compiled[0]);
        $this->assertStringContainsString('[FooModel].[age] between :_', $compiled[0]);
        $this->assertStringContainsString('[FooModel].[deleted] = 0', $compiled[0]);
        $this->assertContains('%active%', $compiled[1]);
        $this->assertContains(18, $compiled[1]);
        $this->assertContains(40, $compiled[1]);

        $compiled = $controller->defaultFilterCondition([
            [
                'field' => 'id',
                'operator' => 'contains',
                'value' => [1, 2],
            ],
        ], ['id']);

        $this->assertIsArray($compiled);
        $this->assertStringContainsString('[FooModel].[id] in ({_', $compiled[0]);
        $this->assertSame([[1, 2]], array_values($compiled[1]));

        foreach ([
            'missing field' => [
                'A valid filter field property is required.',
                [['operator' => '=', 'value' => 1]],
            ],
            'missing operator' => [
                'A valid filter operator property is required.',
                [['field' => 'status', 'value' => 'active']],
            ],
            'unauthorized field' => [
                'Unauthorized filter field "status".',
                [['field' => 'status', 'operator' => '=', 'value' => 'active']],
            ],
            'unsupported operator' => [
                'Unsupported filter operator "roughly".',
                [['field' => 'status', 'operator' => 'roughly', 'value' => 'active']],
                ['status'],
            ],
            'is operator with value' => [
                'Operator "is null" does not accept a value.',
                [['field' => 'status', 'operator' => 'is null', 'value' => 'active']],
                ['status'],
            ],
        ] as $case) {
            [$message, $filters, $allowed] = [$case[0], $case[1], $case[2] ?? []];

            try {
                $controller->defaultFilterCondition($filters, $allowed);
                $this->fail('Expected filter compiler error: ' . $message);
            }
            catch (\Exception $exception) {
                $this->assertSame($message, $exception->getMessage());
            }
        }
    }

    public function testFilterProtectedHelpersHandleScopeLogicAndBindTypes(): void
    {
        $controller = $this->newQueryController([
            'filters' => [
                ['field' => 'status'],
                [
                    ['field' => 'type'],
                    ['field' => 'createdBy'],
                ],
            ],
        ]);

        $this->assertSame(['=', 7], $controller->exposeOptimizeOperatorAndValue('contains', 7));
        $this->assertSame(['in', [7, 8]], $controller->exposeOptimizeOperatorAndValue('contains', [7, 8]));
        $this->assertSame(['!=', 7], $controller->exposeOptimizeOperatorAndValue('does not contain', 7));
        $this->assertSame(['contains', '7'], $controller->exposeOptimizeOperatorAndValue('contains', '7'));

        $this->assertSame('Comment', $controller->exposeGetExistentialUniverseField('Comment.content'));
        $this->assertSame('Comment[a]', $controller->exposeGetExistentialUniverseField('Comment[a].content'));
        $this->assertSame('(foo) AND (bar)', $controller->exposeMergeSqlConditions('foo', 'bar'));
        $this->assertSame('foo', $controller->exposeMergeSqlConditions('', 'foo'));

        $this->assertSame('(a and b)', $controller->exposeAssembleLegacyGroupSql(['and a', 'and b'], 0));
        $this->assertSame('or (a and b)', $controller->exposeAssembleLegacyGroupSql(['or a', 'and b'], 1));

        $this->assertSame(['field', null, 'field', null], $controller->exposeSplitField('field'));
        $this->assertSame(
            ['Author.Profile.email', 'Author.Profile', 'email', 'Author.Profile'],
            $controller->exposeSplitField('Author.Profile.email')
        );

        $this->assertSame('contains', $controller->exposeToPositiveOperator('does not contain'));
        $this->assertSame('in', $controller->exposeToPositiveOperator('not in'));
        $this->assertSame('starts with', $controller->exposeToPositiveOperator('starts with'));

        $this->assertSame('self', $controller->exposeGetFilterScope([
            'field' => 'status',
            'operator' => '=',
        ], null));
        $this->assertSame('existential', $controller->exposeGetFilterScope([
            'field' => 'Author.email',
            'operator' => 'contains',
        ], null));
        $this->assertSame('self', $controller->exposeGetFilterScope([
            'field' => 'Author.email',
            'operator' => 'contains',
        ], 'Author'));
        $this->assertSame('existential', $controller->exposeGetFilterScope([
            'field' => 'Author.id',
            'operator' => '=',
            'subquery' => true,
        ], 'Author'));

        $this->assertSame(Column::BIND_PARAM_STR, $controller->getBindTypeFromRawValue('foo'));
        $this->assertSame(Column::BIND_PARAM_INT, $controller->getBindTypeFromRawValue(1));
        $this->assertSame(Column::BIND_PARAM_BOOL, $controller->getBindTypeFromRawValue(false));
        $this->assertSame(Column::BIND_PARAM_DECIMAL, $controller->getBindTypeFromRawValue(1.5));
        $this->assertSame(Column::BIND_PARAM_NULL, $controller->getBindTypeFromRawValue(null));

        $this->assertSame('Comment', $controller->exposeGetExistentialUniverseField('Comment'));
        $this->assertSame('', $controller->exposeAssembleLegacyGroupSql(['   '], 0));
        $this->assertNull($controller->exposeResolveGroupCarrierLogic([
            [
                [
                    'field' => 'nested',
                    'operator' => '=',
                    'value' => 1,
                ],
            ],
        ]));
        $this->assertNull($controller->exposeResolveGroupCarrierLogic([
            [
                'field' => 'status',
            ],
        ]));
        $this->assertSame('xor', $controller->exposeResolveGroupCarrierLogic([
            [
                'field' => 'status',
                'logic' => 'xor',
            ],
        ]));

        $this->assertTrue($controller->hasFiltersFieldsParams());
        $this->assertTrue($controller->hasFiltersFieldsParams('status'));
        $this->assertTrue($controller->hasFiltersFieldsParams(['status', 'type'], true));
        $this->assertFalse($controller->hasFiltersFieldsParams(['status', 'missing']));
        $this->assertTrue($controller->hasFiltersFieldsParams([['type', 'missing']]));
        $this->assertFalse($controller->hasFiltersFieldsParams([]));

        $this->assertFalse($this->newQueryController()->hasFiltersFieldsParams('status'));
        $this->assertFalse($this->newQueryController(['filters' => []])->hasFiltersFieldsParams('status'));

        $this->assertSame('self', $controller->exposeGetFilterScope([
            'field' => 'status',
        ], null));
        $this->assertSame('self', $controller->exposeGetFilterScope([
            'field' => 'Author.Profile.email',
            'operator' => '=',
        ], 'Author'));
        $this->assertSame('self', $controller->exposeGetFilterScope([
            'field' => 'Author.id',
            'operator' => '=',
        ], null));
    }

    public function testCompileSingleFilterConditionCoversOperatorFamilies(): void
    {
        $controller = $this->newQueryController();
        $counter = 0;
        $makeBind = static function (string $suffix) use (&$counter): string {
            return 'b' . (++$counter) . '_' . $suffix;
        };

        [$sql, $bind, $bindTypes] = $controller->exposeCompileSingleFilterCondition(
            '[FooModel].[age]',
            'between',
            ['value' => [40, 18]],
            $makeBind
        );
        $this->assertSame('[FooModel].[age] between :b1_value: and :b2_value:', $sql);
        $this->assertSame([18, 40], array_values($bind));
        $this->assertSame([Column::BIND_PARAM_STR, Column::BIND_PARAM_STR], array_values($bindTypes));

        [$sql, $bind] = $controller->exposeCompileSingleFilterCondition(
            '[FooModel].[status]',
            'not in',
            ['value' => ['draft', 'archived']],
            $makeBind
        );
        $this->assertStringContainsString('[FooModel].[status] not in ({b3_value:array})', $sql);
        $this->assertSame([['draft', 'archived']], array_values($bind));

        [$sql, $bind] = $controller->exposeCompileSingleFilterCondition(
            '[FooModel].[title]',
            'does not contain',
            ['value' => ['foo', 'bar']],
            $makeBind
        );
        $this->assertStringContainsString('not like :b4_value:', $sql);
        $this->assertStringContainsString(' and ', $sql);
        $this->assertSame(['%foo%', '%bar%'], array_values($bind));

        [$sql, $bind] = $controller->exposeCompileSingleFilterCondition(
            '[FooModel].[title]',
            'starts with',
            ['value' => 'foo'],
            $makeBind
        );
        $this->assertStringContainsString('like :b6_value:', $sql);
        $this->assertSame(['foo%'], array_values($bind));

        [$sql, $bind] = $controller->exposeCompileSingleFilterCondition(
            '[FooModel].[title]',
            'ends with',
            ['value' => 'foo'],
            $makeBind
        );
        $this->assertStringContainsString('like :b7_value:', $sql);
        $this->assertSame(['%foo'], array_values($bind));

        [$sql, $bind] = $controller->exposeCompileSingleFilterCondition(
            '[FooModel].[title]',
            'contains word',
            ['value' => 'foo'],
            $makeBind
        );
        $this->assertSame('((regexp([FooModel].[title], :b8_value:)))', $sql);
        $this->assertSame(['\\bfoo\\b'], array_values($bind));

        [$sql, $bind] = $controller->exposeCompileSingleFilterCondition(
            '[FooModel].[title]',
            'regexp',
            ['value' => '^foo'],
            $makeBind
        );
        $this->assertSame('((regexp([FooModel].[title], :b9_value:)))', $sql);
        $this->assertSame(['^foo'], array_values($bind));

        [$sql, $bind, $bindTypes] = $controller->exposeCompileSingleFilterCondition(
            '[FooModel].[point]',
            'distance sphere less than or equal',
            ['value' => [1.1, 2.2, 3.3, 4.4, 100]],
            $makeBind
        );
        $this->assertStringContainsString('ST_Distance_Sphere(point(:b10_value:, :b11_value:)', $sql);
        $this->assertStringContainsString(') <= :b14_value:', $sql);
        $this->assertSame([1.1, 2.2, 3.3, 4.4, 100], array_values($bind));
        $this->assertSame([
            Column::BIND_PARAM_DECIMAL,
            Column::BIND_PARAM_DECIMAL,
            Column::BIND_PARAM_DECIMAL,
            Column::BIND_PARAM_DECIMAL,
            Column::BIND_PARAM_STR,
        ], array_values($bindTypes));

        $this->assertSame(['', [], []], $controller->exposeCompileSingleFilterCondition(
            '[FooModel].[status]',
            '=',
            ['value' => []],
            $makeBind
        ));

        foreach ([
            'negative existential text' => ['does not contain', "Negative text operator 'does not contain' must be normalized"],
            'negative existential scalar' => ['!=', "Negative operator '!=' is not allowed"],
            'no-value existential' => ['is null', "No-value operator 'is null' is not allowed"],
            'not-in existential' => ['not in', "Negative operator 'not in' is not allowed"],
        ] as [$operator, $message]) {
            try {
                $controller->exposeCompileSingleFilterCondition(
                    '[FooModel].[title]',
                    $operator,
                    ['value' => 'foo'],
                    $makeBind,
                    'existential'
                );
                $this->fail('Expected existential compile guard for ' . $operator);
            }
            catch (LogicException $exception) {
                $this->assertStringContainsString($message, $exception->getMessage());
            }
        }
    }

    public function testExistentialHelpersBuildAndFlushBuckets(): void
    {
        $controller = $this->newQueryController();
        $controller->setJoins(new Collection([
            'Author' => [
                'AuthorModel',
                '[FooModel].[authorId] = [Author].[id]',
                'Author',
                'left',
                [
                    'conditions' => '[Author].[deleted] = :authorDeleted:',
                    'bind' => ['authorDeleted' => 0],
                    'bindTypes' => ['authorDeleted' => Column::BIND_PARAM_INT],
                ],
            ],
            'Author.Profile' => [
                'ProfileModel',
                '[Author].[profileId] = [Author.Profile].[id]',
                'Author.Profile',
                'inner',
            ],
        ], false));

        $this->assertSame(
            'existential|Author.Profile|not',
            $controller->exposeGetExistentialBucketKey('Author[a].Profile.name', true, 'existential')
        );

        $exists = $controller->exposeBuildExistsConditionFromField('Author.Profile.email', '[Author.Profile].[email] like :email:');
        $this->assertStringStartsWith('EXISTS (SELECT 1 FROM [ProfileModel] AS [Author.Profile]', $exists['conditions']);
        $this->assertStringContainsString('LEFT JOIN [AuthorModel] AS [Author] ON', $exists['conditions']);
        $this->assertStringContainsString('[Author].[deleted] = :authorDeleted:', $exists['conditions']);
        $this->assertSame(['authorDeleted' => 0], $exists['bind']);

        $pending = [];
        $controller->exposePushExistentialCondition(
            $pending,
            'bucket',
            'Author.Profile.email',
            false,
            '[Author.Profile].[email] like :email:',
            ['email' => '%foo%'],
            ['email' => Column::BIND_PARAM_STR]
        );
        $controller->exposePushExistentialCondition(
            $pending,
            'bucket',
            'Author.Profile.email',
            false,
            '[Author.Profile].[name] like :name:',
            ['name' => '%bar%'],
            ['name' => Column::BIND_PARAM_STR]
        );

        $fragments = [];
        $bind = [];
        $bindTypes = [];
        $controller->exposeFlushExistentialBuckets($pending, $fragments, $bind, $bindTypes);

        $this->assertSame([], $pending);
        $this->assertCount(1, $fragments);
        $this->assertStringContainsString('EXISTS (SELECT 1 FROM [ProfileModel]', $fragments[0]);
        $this->assertSame([
            'email' => '%foo%',
            'name' => '%bar%',
            'authorDeleted' => 0,
        ], $bind);
        $this->assertSame([
            'email' => Column::BIND_PARAM_STR,
            'name' => Column::BIND_PARAM_STR,
            'authorDeleted' => Column::BIND_PARAM_INT,
        ], $bindTypes);

        $pending = [];
        $controller->exposePushExistentialCondition($pending, 'bucket', 'Author.email', false, 'a = :a:', ['a' => 1], []);
        try {
            $controller->exposePushExistentialCondition($pending, 'bucket', 'Author.email', false, 'b = :a:', ['a' => 2], []);
            $this->fail('Expected existential bucket bind collision.');
        }
        catch (LogicException $exception) {
            $this->assertSame('Bind collision detected inside existential bucket.', $exception->getMessage());
        }

        $pending = [];
        $controller->exposePushExistentialCondition($pending, 'bucket', 'Author.email', false, 'a = :a:', [], [
            'a' => Column::BIND_PARAM_INT,
        ]);
        try {
            $controller->exposePushExistentialCondition($pending, 'bucket', 'Author.email', false, 'b = :a:', [], [
                'a' => Column::BIND_PARAM_STR,
            ]);
            $this->fail('Expected existential bucket bind type collision.');
        }
        catch (LogicException $exception) {
            $this->assertSame('BindType collision detected inside existential bucket.', $exception->getMessage());
        }

        $pending = [
            'invalid' => [
                'field' => '',
                'conditions' => [],
            ],
        ];
        $fragments = ['and keep = 1'];
        $bind = [];
        $bindTypes = [];
        $controller->exposeFlushExistentialBuckets($pending, $fragments, $bind, $bindTypes);
        $this->assertSame([], $pending);
        $this->assertSame(['and keep = 1'], $fragments);

        $pending = [
            'invalid' => [
                'field' => 'Author.email',
                'conditions' => ['([Author].[email] = :email:)'],
                'negated' => false,
                'logic' => 'or',
                'bind' => [],
                'bindTypes' => [],
            ],
        ];
        $fragments = [];
        $bind = [];
        $bindTypes = [];
        try {
            $controller->exposeFlushExistentialBuckets($pending, $fragments, $bind, $bindTypes);
            $this->fail('Expected non-AND existential bucket guard.');
        }
        catch (LogicException $exception) {
            $this->assertSame(
                'Existential bucket invariant violated: non-AND bucket encountered.',
                $exception->getMessage()
            );
        }

        $pending = [
            'bucket' => [
                'field' => 'Author.email',
                'conditions' => ['([Author].[email] = :email:)'],
                'negated' => false,
                'logic' => 'and',
                'bind' => ['email' => 'new'],
                'bindTypes' => [],
            ],
        ];
        $fragments = [];
        $bind = ['email' => 'existing'];
        $bindTypes = [];
        try {
            $controller->exposeFlushExistentialBuckets($pending, $fragments, $bind, $bindTypes);
            $this->fail('Expected existential flush bind collision.');
        }
        catch (LogicException $exception) {
            $this->assertSame('Bind collision detected while flushing existential bucket.', $exception->getMessage());
        }

        $pending = [
            'bucket' => [
                'field' => 'Author.email',
                'conditions' => ['([Author].[email] = :email:)'],
                'negated' => false,
                'logic' => 'and',
                'bind' => [],
                'bindTypes' => ['email' => Column::BIND_PARAM_STR],
            ],
        ];
        $fragments = [];
        $bind = [];
        $bindTypes = ['email' => Column::BIND_PARAM_INT];
        try {
            $controller->exposeFlushExistentialBuckets($pending, $fragments, $bind, $bindTypes);
            $this->fail('Expected existential flush bind type collision.');
        }
        catch (LogicException $exception) {
            $this->assertSame('BindType collision detected while flushing existential bucket.', $exception->getMessage());
        }

        $pending = [
            'bucket' => [
                'field' => 'Author.email',
                'conditions' => ['([Author].[email] = :email:)'],
                'negated' => false,
                'logic' => 'and',
                'bind' => [],
                'bindTypes' => [],
            ],
        ];
        $fragments = [];
        $bind = ['authorDeleted' => 1];
        $bindTypes = [];
        try {
            $controller->exposeFlushExistentialBuckets($pending, $fragments, $bind, $bindTypes);
            $this->fail('Expected existential join bind collision.');
        }
        catch (LogicException $exception) {
            $this->assertSame('Bind collision detected while merging EXISTS join binds.', $exception->getMessage());
        }

        $pending = [
            'bucket' => [
                'field' => 'Author.email',
                'conditions' => ['([Author].[email] = :email:)'],
                'negated' => false,
                'logic' => 'and',
                'bind' => [],
                'bindTypes' => [],
            ],
        ];
        $fragments = [];
        $bind = [];
        $bindTypes = ['authorDeleted' => Column::BIND_PARAM_STR];
        try {
            $controller->exposeFlushExistentialBuckets($pending, $fragments, $bind, $bindTypes);
            $this->fail('Expected existential join bind type collision.');
        }
        catch (LogicException $exception) {
            $this->assertSame('BindType collision detected while merging EXISTS join bindTypes.', $exception->getMessage());
        }
    }

    public function testSaveHelpersResolveAssignPersistAndFailPredictably(): void
    {
        $controller = $this->newQueryController();

        $this->assertTrue($controller->exposeHasPrimaryKey(['id' => 1]));
        $this->assertTrue($controller->exposeHasPrimaryKey(['uuid' => 'abc']));
        $this->assertFalse($controller->exposeHasPrimaryKey(['name' => 'Alice']));

        $payload = ['id' => 1, 'uuid' => 'abc', 'name' => 'Alice'];
        $controller->exposeStripPrimaryKey($payload);
        $this->assertSame(['name' => 'Alice'], $payload);

        $failure = $controller->exposeBuildRestSaveFailure('Nope.', 'NopeType', 418, 'id');
        $this->assertFalse($failure['saved']);
        $this->assertSame('Nope.', $failure['messages'][0]->getMessage());
        $this->assertSame('id', $failure['messages'][0]->getField());
        $this->assertSame('NopeType', $failure['messages'][0]->getType());
        $this->assertSame(418, $failure['messages'][0]->getCode());

        $model = $this->createMock(ModelInterface::class);
        $controller->unitModel = $model;

        $this->assertSame(['create', $model, null], $controller->exposeResolvePersistenceIntent(['name' => 'Alice'], null));
        $this->assertSame(['create', $model, null], $controller->exposeResolvePersistenceIntent(['name' => 'Alice'], 'create'));

        $createWithIdentity = $controller->exposeResolvePersistenceIntent(['id' => 1], 'create');
        $this->assertNull($createWithIdentity[0]);
        $this->assertNull($createWithIdentity[1]);
        $this->assertFalse($createWithIdentity[2]['saved']);

        $updateWithoutIdentity = $controller->exposeResolvePersistenceIntent(['name' => 'Alice'], 'update');
        $this->assertNull($updateWithoutIdentity[0]);
        $this->assertNull($updateWithoutIdentity[1]);
        $this->assertFalse($updateWithoutIdentity[2]['saved']);

        $controller->unitFindFirstModel = $model;
        $this->assertSame(['update', $model, null], $controller->exposeResolvePersistenceIntent(['id' => 1], 'update'));
        $this->assertArrayHasKey('conditions', $controller->unitLastFind);

        $controller->setSaveFields(new Collection(['name']));
        $controller->setMapFields(new Collection(['publicName' => 'name'], false));
        $assignPayload = ['id' => 1, 'name' => 'Alice'];

        $model->expects($this->once())
            ->method('assign')
            ->with(['name' => 'Alice'], ['name'], ['publicName' => 'name'])
            ->willReturnSelf();

        $controller->exposeAssignModelFromPayload($model, $assignPayload);
        $this->assertSame(['name' => 'Alice'], $assignPayload);
    }

    public function testAssignModelFromPayloadAllowsBeforeAssignListenersToMutatePayloadAndPolicies(): void
    {
        $controller = $this->newQueryController();
        $controller->setSaveFields(new Collection(['name'], false));
        $controller->setMapFields(new Collection(['publicName' => 'name'], false));

        $eventsManager = new \Phalcon\Events\Manager();
        $eventsManager->attach(
            'rest',
            static function (\Phalcon\Events\Event $event, Restful $source, array $eventData): void {
                if ($event->getType() !== 'beforeAssign') {
                    return;
                }

                $eventData[1]['clientName'] = $eventData[1]['name'];
                unset($eventData[1]['id'], $eventData[1]['name']);

                $eventData[2] = ['clientName'];
                $eventData[3] = ['clientName' => 'name'];
            }
        );
        $controller->setEventsManager($eventsManager);

        $model = new QueryModelDouble();
        $assignPayload = [
            'id' => 1,
            'name' => 'Alice',
        ];

        $controller->exposeAssignModelFromPayload($model, $assignPayload);

        $this->assertSame(['clientName' => 'Alice'], $assignPayload);
        $this->assertSame(['clientName' => 'Alice'], $model->assignedData);
        $this->assertSame(['clientName'], $model->assignedWhiteList);
        $this->assertSame(['clientName' => 'name'], $model->assignedColumnMap);
    }

    public function testPersistAssignedModelAndSaveEntryPoints(): void
    {
        $controller = $this->newQueryController([
            'name' => 'Alice',
        ]);

        $model = $this->createStub(ModelInterface::class);
        $model->method('assign')->willReturnSelf();
        $model->method('save')->willReturn(true);
        $model->method('getMessages')->willReturn([]);
        $controller->unitModel = $model;

        $result = $controller->exposePersistAssignedModel($model, 'create');
        $this->assertSame([
            'saved' => true,
            'mode' => 'create',
            'data' => ['exposed' => true],
            'messages' => [],
        ], $result);

        $this->assertSame('create', $controller->create()['mode']);

        $controller = $this->newQueryController([
            'id' => 5,
            'name' => 'Alice',
        ]);
        $model = $this->createStub(ModelInterface::class);
        $model->method('assign')->willReturnSelf();
        $model->method('save')->willReturn(true);
        $model->method('getMessages')->willReturn([]);
        $controller->unitModel = $model;
        $controller->unitFindFirstModel = $model;

        $this->assertSame('update', $controller->update()['mode']);

        $controller = $this->newQueryController([
            ['name' => 'Alice'],
            'invalid',
        ]);
        $model = $this->createStub(ModelInterface::class);
        $model->method('assign')->willReturnSelf();
        $model->method('save')->willReturn(true);
        $model->method('getMessages')->willReturn([]);
        $controller->unitModel = $model;

        $batch = $controller->save();
        $this->assertFalse($batch['saved']);
        $this->assertSame(['total' => 2, 'saved' => 1, 'failed' => 1], $batch['stats']);
        $this->assertSame('Invalid payload row.', $batch['results'][1]['messages'][0]->getMessage());

        $controller = $this->newQueryController();
        $failingModel = $this->createStub(ModelInterface::class);
        $failingModel->method('save')->willReturn(false);
        $failingModel->method('getMessages')->willReturn(['validation failed']);

        $failed = $controller->exposePersistAssignedModel($failingModel, 'create');
        $this->assertSame([
            'saved' => false,
            'messages' => ['validation failed'],
        ], $failed);

        $this->expectException(PhalconKitLogicException::class);
        $this->expectExceptionMessage('Persistence intent resolved without a model instance.');

        $controller->exposeRequireResolvedPersistenceIntent('create', null);
    }

    public function testTopLevelQueryHelpersPrepareCalculationAndEvents(): void
    {
        $controller = $this->newQueryController([
            'lifetime' => 60,
            'limit' => 15,
            'offset' => 3,
            'order' => 'id desc',
            'distinct' => 'id',
        ]);

        $controller->initializeQuery();

        $find = $controller->prepareFind();
        $this->assertSame(15, $find['limit']);
        $this->assertSame(3, $find['offset']);
        $this->assertSame('id', $find['distinct']);
        $this->assertSame('[FooModel].[id] desc', $find['order']);
        $this->assertSame(60, $find['cache']['lifetime']);
        $this->assertSame('id, [FooModel].[name]', $controller->exposePrepareFindListToString([
            'id' => true,
            'name' => '[FooModel].[name]',
        ]));

        $columnController = $this->newQueryController();
        $columnController->initializeColumn();
        $columnController->mergeColumn(new Collection(['status' => true]));
        $columnController->initializeFind();
        $find = $columnController->prepareFind();
        $this->assertSame('status', $find['column']);

        $this->assertFalse($controller->conditionsShouldBeHaving('COUNT(id) > 1'));
        $this->assertStringStartsWith('_query_', $controller->generateBindKey('query'));

        $calculation = $controller->exposeGetCalculationFind([
            'conditions' => 'active = 1',
            'limit' => 10,
            'offset' => 20,
            'group' => ['id', 'status'],
            'empty' => '',
        ]);
        $this->assertSame([
            'conditions' => 'active = 1',
            'group' => 'id, status',
        ], $calculation);

        $calculation = $controller->exposeGetCalculationFind([
            'conditions' => '0',
            'limit' => 10,
            'offset' => 20,
        ]);
        $this->assertSame([
            'conditions' => '0',
        ], $calculation);

        $calculation = $controller->exposeGetCalculationFind([
            'limit' => 10,
            'offset' => 20,
        ], false);
        $this->assertSame([
            'limit' => 10,
            'offset' => 20,
        ], $calculation);
    }

    public function testMergeHelpersAndConditionFallbackBranches(): void
    {
        $controller = $this->newQueryController();

        $controller->mergeCacheConfig(new Collection(['lifetime' => 60]));
        $controller->mergeCacheConfig(new Collection(['key' => 'cache-key']));
        $this->assertSame(['lifetime' => 60, 'key' => 'cache-key'], $controller->getCacheConfig()?->toArray());

        $controller->mergeConditions(new Collection(['one' => 'a = 1'], false));
        $controller->mergeConditions(new Collection(['two' => 'b = 1'], false));
        $this->assertSame(['one' => 'a = 1', 'two' => 'b = 1'], $controller->getConditions()?->toArray());

        $controller->mergeDistinct(new Collection(['id' => true], false));
        $controller->mergeDistinct(new Collection(['name' => true], false));
        $this->assertSame(['id' => true, 'name' => true], $controller->getDistinct()?->toArray());

        $controller->mergeGroup(new Collection(['id' => '[FooModel].[id]'], false));
        $controller->mergeGroup(new Collection(['status' => '[FooModel].[status]'], false));
        $this->assertSame([
            'id' => '[FooModel].[id]',
            'status' => '[FooModel].[status]',
        ], $controller->getGroup()?->toArray());

        $controller->setMaxLimit(null);
        $this->assertSame(100, $controller->defaultMaxLimit());

        $controller->unitCreatedByColumns = [];
        $this->assertNull($controller->buildDefaultPermissionCondition());

        $controller->unitSoftDeleteColumn = null;
        $this->assertNull($controller->buildDefaultSoftDeleteCondition());

        $controller->unitIdentityColumns = [];
        $this->assertNull($controller->buildIdentityConditionFromData(['id' => 1]));
        $controller->unitIdentityColumns = ['id'];
        $this->assertNull($controller->buildIdentityConditionFromData(['id' => null]));

        $this->assertNull($controller->buildDefaultSearchCondition());

        $controller = $this->newQueryController(['search' => 'alpha']);
        $this->assertNull($controller->buildDefaultSearchCondition());

        $controller->setSearchFields(new Collection([
            'disabled' => false,
        ], false));
        $this->assertNull($controller->buildDefaultSearchCondition());
        $this->assertSame(['alpha'], $controller->extractSearchTerms());

        $controller = $this->newQueryController(['search' => 123]);
        $this->assertSame([], $controller->extractSearchTerms());

        $controller = $this->newStaticQueryController();
        $this->assertSame(['createdBy'], $controller->getCreatedByColumns());
        $this->assertSame('deleted', $controller->getSoftDeleteColumn());
        $this->assertSame(['id'], $controller->getIdentityColumns());
    }

    public function testCompilerCoversEmptyCleanupAndValidationBranches(): void
    {
        $controller = $this->newQueryController();

        $this->assertSame([], $controller->compileFind([]));
        $this->assertSame([
            'conditions' => [],
            'joins' => [],
        ], $controller->mergeCompiledFind([
            'conditions' => null,
            'joins' => null,
            'bind' => null,
        ]));
        $this->assertSame('((a = 1)) AND ((b = 1))', $controller->compileFinds(
            ['conditions' => 'a = 1'],
            ['conditions' => ['b = 1']]
        )['conditions']);

        $controller->setFind(new Collection([
            'distinct' => new Collection(['empty' => ''], false),
        ], false));
        $this->assertSame([], $controller->prepareFind());

        $find = [
            'group' => 'status',
            'order' => '',
            'columns' => 'id',
        ];
        $controller->beforeCompileFind($find);
        $this->assertSame(['status'], $find['group']);
        $this->assertSame('', $find['order']);
        $this->assertSame(['id'], $find['columns']);

        $find = [
            'conditions' => [],
            'limit' => '5',
            'offset' => '2',
            'group' => ['status', 'status', 'type'],
            'order' => ['id desc', ''],
            'distinct' => ['id', 'id'],
            'dropNull' => null,
            'dropEmpty' => [],
            'dropString' => '',
        ];
        $controller->afterCompileFind($find);
        $this->assertSame([
            'limit' => 5,
            'offset' => 2,
            'group' => 'status, type',
            'order' => 'id desc',
            'distinct' => 'id',
        ], $find);

        $merged = ['conditions' => [' a = 1 ', '', 'a = 1']];
        $controller->afterMergeCompileFind($merged);
        $this->assertSame(['conditions' => '(a = 1)'], $merged);

        $merged = ['conditions' => ['', '   ']];
        $controller->afterMergeCompileFind($merged);
        $this->assertSame([], $merged);

        $compiled = $controller->compileFind([
            'conditions' => [
                ['x = 1'],
                ['conditions' => 'nested = 1'],
                ['conditions' => ['deep = 1']],
                ['group' => ['status', 'status']],
            ],
        ]);
        $this->assertSame('(x = 1) AND (nested = 1) AND ((deep = 1))', $compiled['conditions']);
        $this->assertSame('status', $compiled['group']);

        foreach ([
            'invalid bind' => [
                'Invalid bind value.',
                fn() => $controller->compileFind([0 => 'id = :id:', 1 => 'bad']),
            ],
            'invalid bind type' => [
                'Invalid bind type.',
                fn() => $controller->compileFind([0 => 'id = :id:', 2 => 'bad']),
            ],
            'invalid existing bind' => [
                'Invalid existing bind value: expected array.',
                fn() => $controller->compileFind([0 => 'id = :id:', 1 => [], 'bind' => 'bad']),
            ],
            'invalid existing bind types' => [
                'Invalid existing bindTypes value: expected array.',
                fn() => $controller->compileFind([0 => 'id = :id:', 2 => [], 'bindTypes' => 'bad']),
            ],
            'invalid merged conditions' => [
                'Invalid merged conditions: expected array.',
                function () use ($controller): void {
                    $merged = ['conditions' => 'bad'];
                    $controller->afterMergeCompileFind($merged);
                },
            ],
            'integer root key' => [
                'integer keys are not allowed',
                fn() => $controller->mergeCompiledFind([0 => 'bad']),
            ],
            'invalid list value' => [
                'Invalid joins value: expected array|string|null.',
                fn() => $controller->mergeCompiledFind(['joins' => new \stdClass()]),
            ],
            'invalid bind map' => [
                'Invalid bind value: expected array.',
                fn() => $controller->mergeCompiledFind(['bind' => 'bad']),
            ],
        ] as $case) {
            [$message, $callback] = $case;

            try {
                $callback();
                $this->fail('Expected compiler branch to throw: ' . $message);
            }
            catch (LogicException $exception) {
                $this->assertStringContainsString($message, $exception->getMessage());
            }
        }
    }

    public function testQueryEntryPointsDelegateToModelStatics(): void
    {
        QueryModelDouble::reset();
        QueryModelDouble::$resultset = $this->createStub(ResultsetInterface::class);
        QueryModelDouble::$aggregateResults = [
            'average' => 12.5,
            'count' => 7,
            'sum' => 99.9,
            'maximum' => 50,
            'minimum' => 2,
        ];

        $model = new QueryModelDouble();
        QueryModelDouble::$first = $model;

        $controller = $this->newStaticQueryController();
        $controller->unitModel = $model;
        $controller->setWith(new Collection(['Author']));
        $controller->setFind(new Collection([
            'conditions' => 'active = 1',
            'limit' => 10,
            'offset' => 20,
            'group' => new Collection(['status']),
        ]));

        $this->assertSame(QueryModelDouble::$resultset, $controller->find());
        $this->assertSame('(active = 1)', QueryModelDouble::$calls['find']['conditions']);

        $this->assertSame(['with' => [['Author'], ['conditions' => 'with = 1']]], $controller->findWith(null, [
            'conditions' => 'with = 1',
        ]));

        $this->assertSame($model, $controller->findFirst(['conditions' => 'id = 1']));
        $this->assertSame(['conditions' => 'id = 1'], QueryModelDouble::$calls['findFirst']);

        $this->assertSame($model, $controller->findFirstWith(null, ['conditions' => 'first = 1']));
        $this->assertSame([['Author'], ['conditions' => 'first = 1']], QueryModelDouble::$calls['findFirstWith']);

        $this->assertSame(12.5, $controller->average());
        $this->assertSame(7, $controller->count());
        $this->assertSame(99.9, $controller->sum());
        $this->assertSame(50.0, $controller->maximum());
        $this->assertSame(2.0, $controller->minimum());
        $this->assertSame([
            'conditions' => '(active = 1)',
            'group' => 'status',
        ], QueryModelDouble::$calls['minimum']);
    }

    public function testWithQueriesRequireEagerLoadModelContract(): void
    {
        $controller = $this->newStaticQueryController();
        $controller->unitModel = $this->createStub(ModelInterface::class);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('must implement "' . EagerLoadInterface::class . '"');

        $controller->findWith();
    }

    public function testWithQueriesNormalizeConfiguredEnabledMapDefaults(): void
    {
        QueryModelDouble::reset();
        QueryModelDouble::$resultset = $this->createStub(ResultsetInterface::class);

        $model = new QueryModelDouble();
        QueryModelDouble::$first = $model;

        $constraint = static fn(): null => null;
        $controller = $this->newStaticQueryController();
        $controller->unitModel = $model;
        $controller->setWith(new Collection([
            'Author' => $constraint,
            'Author.Profile' => true,
            'Comments' => 'off',
            'Audit' => 0,
            'Hidden' => false,
            'Blank' => '',
            'Legacy',
        ], false));

        $expectedWith = [
            'Author' => $constraint,
            'Author.Profile',
            'Legacy',
        ];

        $this->assertSame(['with' => [$expectedWith, ['conditions' => 'with = 1']]], $controller->findWith(null, [
            'conditions' => 'with = 1',
        ]));
        $this->assertSame([$expectedWith, ['conditions' => 'with = 1']], QueryModelDouble::$calls['findWith']);

        $this->assertSame($model, $controller->findFirstWith(null, ['conditions' => 'first = 1']));
        $this->assertSame([$expectedWith, ['conditions' => 'first = 1']], QueryModelDouble::$calls['findFirstWith']);
    }

    public function testCountUsesDistinctPrimaryKeyWhenJoinsWouldDuplicateRows(): void
    {
        QueryModelDouble::reset();
        QueryModelDouble::$aggregateResults = [
            'count' => 7,
        ];

        $controller = $this->newStaticQueryController();
        $controller->unitModel = new QueryModelDouble();

        $this->assertSame(7, $controller->count([
            'conditions' => 'active = 1',
            'joins' => [
                [QueryModelDouble::class, '[FooModel].[id] = [Related].[fooId]', 'Related', 'left'],
            ],
        ]));
        $this->assertSame(
            'DISTINCT [FooModel].[id]',
            QueryModelDouble::$calls['count']['column']
        );

        $this->assertSame(7, $controller->count([
            'joins' => [
                [QueryModelDouble::class, '[FooModel].[id] = [Related].[fooId]', 'Related', 'left'],
            ],
            'column' => '[FooModel].[status]',
        ]));
        $this->assertSame('[FooModel].[status]', QueryModelDouble::$calls['count']['column']);

        $controller->unitPrimaryKeyAttributes = ['tenantId', 'id'];

        $this->assertSame(7, $controller->count([
            'joins' => [
                [QueryModelDouble::class, '[FooModel].[id] = [Related].[fooId]', 'Related', 'left'],
            ],
        ]));
        $this->assertArrayNotHasKey('column', QueryModelDouble::$calls['count']);
    }

    public function testJoinAndDynamicJoinBranches(): void
    {
        $controller = $this->newQueryController();

        $this->assertSame([], $controller->getDynamicJoinsFromFilters(null));
        $this->assertSame([], $controller->getJoinsDefinitionFromField('Author.email'));

        $controller = $this->newQueryController([
            'filters' => [
                [
                    'field' => 'Author.email',
                    'operator' => 'contains',
                    'value' => 'foo',
                ],
            ],
        ]);
        $this->assertSame([], $controller->getDynamicJoinsFromFilters());

        $controller = $this->newQueryController([
            'filters' => [
                [
                    'field' => 'Author.email',
                    'operator' => 'contains',
                    'value' => 'foo',
                ],
            ],
        ]);
        $controller->setDynamicJoins(new Collection([
            'Author' => ['AuthorModel', [
                '[FooModel].[authorId] = [Author].[id]',
                '[Author].[deleted] = 0',
            ]],
        ], false));
        $join = current($controller->getJoins()?->toArray() ?? []);
        $this->assertStringContainsString(' and ', $join[1]);

        $controller = $this->newQueryController([
            'filters' => [
                [
                    'field' => 'Author.email',
                    'operator' => 'contains',
                    'value' => 'foo',
                ],
            ],
        ]);

        try {
            $controller->setDynamicJoins(new Collection([
                'Author' => ['AuthorModel', 123],
            ], false));
            $this->fail('Expected invalid dynamic join condition to throw.');
        }
        catch (LogicException $exception) {
            $this->assertSame('Invalid dynamic join condition for `Author`.', $exception->getMessage());
        }

        $controller = $this->newQueryController([
            'filters' => [
                [
                    'field' => 'Author.email',
                    'operator' => 'contains',
                    'value' => 'foo',
                ],
            ],
        ]);
        $controller->setDynamicJoins(new Collection([
            'Author' => ['AuthorModel', '[FooModel].[authorId] = [Author].[id]', 'Author', 123],
        ], false));
        $join = current($controller->getJoins()?->toArray() ?? []);
        $this->assertSame('left', $join[3]);

        $controller = $this->newQueryController([
            'filters' => [
                [
                    'field' => 'Author.email',
                    'operator' => 'contains',
                    'value' => 'foo',
                ],
            ],
        ]);
        $controller->exposeSetDynamicJoinState(['Author' => 'known'], [
            'orphan' => ['Model', '1 = 1', 'Alias'],
        ]);
        $controller->setDynamicJoins(new Collection([
            'Author' => ['AuthorModel', '[FooModel].[authorId] = [Author].[id]'],
        ], false));
        $this->assertFalse($controller->getJoins()?->has('orphan'));

        $controller = $this->newQueryController();
        $controller->exposeSetDynamicJoinState(['Author' => 'Mapped'], []);
        $controller->setJoins(new Collection([
            'Author' => ['AuthorModel', '1 = 1', 'Author'],
        ], false));
        $this->assertSame([
            ['AuthorModel', '1 = 1', 'Author'],
        ], $controller->getJoinsDefinitionFromField('Author.email'));

        $controller = $this->newQueryController();
        $controller->exposeSetDynamicJoinState(['Author' => 'Mapped'], []);
        $controller->setJoins(new Collection([
            ['AuthorModel', '1 = 1', 'Author'],
        ], false));
        $this->assertSame([
            ['AuthorModel', '1 = 1', 'Author'],
        ], $controller->getJoinsDefinitionFromField('Author.email'));

        $controller = $this->newQueryController([
            'filters' => [
                [
                    'field' => 'Author.Profile.email',
                    'operator' => 'contains',
                    'value' => 'foo',
                ],
            ],
        ]);
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Dynamic join alias not defined for `Author`');
        $controller->setDynamicJoins(new Collection([
            'Author.Profile' => ['ProfileModel', '1 = 1'],
        ], false));
    }

    public function testJoinPayloadValidationAndFallbackLookupBranches(): void
    {
        $controller = $this->newQueryController();

        $payload = $controller->exposeNormalizeJoinPayload([
            [
                'conditions' => '',
            ],
            [
                0 => 'x = :x:',
                1 => ['x' => 1],
                2 => ['x' => Column::BIND_PARAM_INT],
            ],
        ]);
        $this->assertSame('(x = :x:)', $payload[0]);
        $this->assertSame(['x' => 1], $payload[1]);

        $this->assertSame(['', [], []], $controller->exposeNormalizeJoinPayload([
            [
                'conditions' => '',
            ],
        ]));

        foreach ([
            'invalid block' => [
                'Invalid join payload block at join index 0, block 1.',
                [['conditions' => 'x = 1'], 'bad'],
            ],
            'invalid condition' => [
                'Join payload condition must be a string at join index 0, block 0.',
                [['conditions' => ['bad']]],
            ],
            'invalid bindTypes' => [
                'Join payload bindTypes must be an array at join index 0, block 0.',
                [['bindTypes' => 'bad']],
            ],
        ] as $case) {
            [$message, $payload] = $case;

            try {
                $controller->exposeNormalizeJoinPayload($payload);
                $this->fail('Expected join payload branch to throw: ' . $message);
            }
            catch (LogicException $exception) {
                $this->assertSame($message, $exception->getMessage());
            }
        }

        $this->assertSame('a = 1', $controller->exposeMergeSqlConditions('', 'a = 1'));
        $this->assertSame('a = 1', $controller->exposeMergeSqlConditions('a = 1', ''));

        $controller->setJoins(new Collection([
            ['Ignored'],
            ['AuthorModel', '1 = 1', 'Author'],
        ], false));

        $this->assertSame([
            ['AuthorModel', '1 = 1', 'Author'],
        ], $controller->getJoinsDefinitionFromField('Author.email'));
    }

    public function testFilterConditionBranchCoverage(): void
    {
        $controller = $this->newQueryController();

        $this->assertNull($controller->defaultFilterCondition([], ['status']));
        $this->assertNull($controller->defaultFilterCondition(['garbage'], ['status']));
        $this->assertFalse($controller->isFilterAllowed('missing', ['status']));
        $this->assertFalse($controller->isJoinFilterAllowed('Author.email', []));
        $this->assertSame(Column::BIND_PARAM_STR, $controller->getBindTypeFromRawValue(['array']));
        $this->assertSame('custom', $controller->exposeToPositiveOperator('not custom'));
        $this->assertSame('custom', $controller->exposeToPositiveOperator('does not custom'));

        try {
            $controller->exposeToPositiveOperator('xnotx');
            $this->fail('Expected unconvertible negative operator to throw.');
        }
        catch (LogicException $exception) {
            $this->assertStringContainsString(
                "Unable to convert negative operator 'xnotx'",
                $exception->getMessage()
            );
        }

        try {
            $controller->exposeGetFilterScope([], null);
            $this->fail('Expected missing field scope guard.');
        }
        catch (LogicException $exception) {
            $this->assertSame('Cannot determine filter scope without field.', $exception->getMessage());
        }

        try {
            $controller->defaultFilterCondition([
                [
                    'field' => 'status',
                    'operator' => '=',
                    'value' => 'active',
                    'logic' => 'nand',
                ],
            ], ['status']);
            $this->fail('Expected unsupported logical operator.');
        }
        catch (\Exception $exception) {
            $this->assertSame('Unsupported logical operator: `nand`', $exception->getMessage());
        }

        $compiled = $controller->defaultFilterCondition([
            [
                [
                    'field' => 'status',
                    'operator' => '=',
                    'value' => 'active',
                    'logic' => 'or',
                ],
                [
                    'field' => 'type',
                    'operator' => '=',
                    'value' => 'admin',
                ],
            ],
            [
                'field' => 'deletedAt',
                'operator' => 'is null',
            ],
            [
                'field' => 'enabled',
                'operator' => 'is true',
            ],
        ], ['status', 'type', 'deletedAt', 'enabled']);

        $this->assertIsArray($compiled);
        $this->assertStringContainsString('[FooModel].[status] = :_', $compiled[0]);
        $this->assertStringContainsString('[FooModel].[deletedAt] is null', $compiled[0]);
        $this->assertStringContainsString('[FooModel].[enabled] = 1', $compiled[0]);

        try {
            $controller->defaultFilterCondition([
                [
                    'field' => 'status',
                    'operator' => '=',
                ],
            ], ['status']);
            $this->fail('Expected missing filter value guard.');
        }
        catch (\Exception $exception) {
            $this->assertSame('Operator "=" requires a value.', $exception->getMessage());
        }
    }

    public function testExistentialFilterConditionBranches(): void
    {
        $controller = $this->newQueryController();
        $controller->setJoins(new Collection([
            'Author' => [
                'AuthorModel',
                '[FooModel].[authorId] = [Author].[id]',
                'Author',
                'left',
            ],
        ], false));

        $compiled = $controller->defaultFilterCondition([
            [
                'field' => 'Author.email',
                'operator' => 'is empty',
                'subquery' => true,
            ],
            [
                'field' => 'Author.name',
                'operator' => 'is not empty',
                'subquery' => true,
            ],
        ], ['Author.email', 'Author.name']);

        $this->assertIsArray($compiled);
        $this->assertStringContainsString('NOT EXISTS', $compiled[0]);
        $this->assertStringContainsString('EXISTS', $compiled[0]);

        $compiled = $controller->defaultFilterCondition([
            [
                'field' => 'Author.email',
                'operator' => 'contains',
                'value' => 'alpha',
            ],
            [
                'field' => 'Author.email',
                'operator' => 'contains',
                'value' => 'beta',
                'logic' => 'or',
            ],
            [
                'field' => 'Author.id',
                'operator' => '!=',
                'value' => 7,
                'subquery' => true,
            ],
        ], ['Author.email', 'Author.id']);

        $this->assertIsArray($compiled);
        $this->assertStringContainsString('or EXISTS', $compiled[0]);
        $this->assertStringContainsString('NOT EXISTS', $compiled[0]);

        $this->assertNull($controller->defaultFilterCondition([
            [
                'field' => 'Author.email',
                'operator' => 'contains',
                'value' => [],
            ],
        ], ['Author.email']));

        $controllerWithJoinPayload = $this->newQueryController();
        $controllerWithJoinPayload->setJoins(new Collection([
            'Author' => [
                'AuthorModel',
                '[FooModel].[authorId] = [Author].[id]',
                'Author',
                'left',
                [
                    'conditions' => '[Author].[deleted] = :authorDeleted:',
                    'bind' => ['authorDeleted' => 0],
                    'bindTypes' => ['authorDeleted' => Column::BIND_PARAM_INT],
                ],
            ],
        ], false));
        $compiled = $controllerWithJoinPayload->defaultFilterCondition([
            [
                'field' => 'Author.email',
                'operator' => 'contains',
                'value' => 'alpha',
                'logic' => 'or',
            ],
        ], ['Author.email']);
        $this->assertIsArray($compiled);
        $this->assertSame(0, $compiled[1]['authorDeleted']);
        $this->assertSame(Column::BIND_PARAM_INT, $compiled[2]['authorDeleted']);

        try {
            $controller->defaultFilterCondition([
                [
                    'field' => 'Author.email',
                    'operator' => 'contains',
                ],
            ], ['Author.email']);
            $this->fail('Expected missing existential filter value.');
        }
        catch (\Exception $exception) {
            $this->assertSame('Operator "contains" requires a value.', $exception->getMessage());
        }

        try {
            $controller->exposeBuildExistsConditionFromField('Missing.email', '1 = 1');
            $this->fail('Expected missing existential join definition.');
        }
        catch (\Exception $exception) {
            $this->assertStringContainsString(
                'Unable to prepare existential subquery for the foreign field "Missing.email".',
                $exception->getMessage()
            );
        }
    }

    public function testSaveFailureBranchesAndEagerLoadAfterSave(): void
    {
        $controller = $this->newQueryController([
            ['id' => 9],
        ]);
        $batch = $controller->save('update');
        $this->assertFalse($batch['saved']);
        $this->assertSame(['total' => 1, 'saved' => 0, 'failed' => 1], $batch['stats']);
        $this->assertSame('Entity not found.', $batch['results'][0]['messages'][0]->getMessage());

        $controller = $this->newQueryController([
            'id' => 9,
        ]);
        $create = $controller->create();
        $this->assertFalse($create['saved']);
        $this->assertSame('Identity fields are not allowed when forcing create.', $create['messages'][0]->getMessage());

        $controller = $this->newQueryController();
        $controller->unitIdentityColumns = [];
        $this->assertNull($controller->exposeFindModelByIdentityPayload(['id' => 1]));

        $controller = $this->newQueryController([
            'id' => 5,
            'name' => 'Alice',
        ]);
        $model = new QueryModelDouble();
        $controller->unitModel = new QueryModelDouble();
        $controller->unitFindFirstModel = $model;
        $this->assertSame('update', $controller->save()['mode']);

        $controller = $this->newQueryController();
        $eventsManager = new \Phalcon\Events\Manager();
        $eventsManager->attach('rest', static function (\Phalcon\Events\Event $event): ?bool {
            return $event->getType() === 'beforeSave' ? false : null;
        });
        $controller->setEventsManager($eventsManager);
        $model = new QueryModelDouble();
        $model->messages = ['blocked'];
        $this->assertSame([
            'saved' => false,
            'messages' => ['blocked'],
        ], $controller->exposePersistAssignedModel($model, 'create'));

        $controller = $this->newQueryController();
        $model = new QueryModelDouble();
        $controller->setWith(new Collection(['Author']));
        $result = $controller->exposePersistAssignedModel($model, 'update');
        $this->assertTrue($result['saved']);
        $this->assertSame([['Author']], $model->loadedWith);
    }

    private function newStaticQueryController(array $params = []): Restful
    {
        $controller = new class extends Restful {
            public object $identity;
            /** @var array<string, mixed> */
            public array $unitParams = [];
            public ?ModelInterface $unitModel = null;
            /** @var array<int, string> */
            public array $unitPrimaryKeyAttributes = ['id'];

            public function initialize(): void
            {
            }

            public function getParam(
                string $key,
                array|string|null $filters = null,
                mixed $default = null,
                ?array $params = null
            ): mixed {
                $params ??= $this->unitParams;

                return array_key_exists($key, $params) ? $params[$key] : $default;
            }

            public function getParams(?array $fields = null, bool $cached = true, bool $deep = true): array
            {
                return $this->unitParams;
            }

            public function hasParam(string $key, ?array $params = null, bool $cached = true): bool
            {
                $params ??= $this->unitParams;
                return array_key_exists($key, $params);
            }

            public function getModelName(): ?string
            {
                return 'FooModel';
            }

            public function appendModelName(string $field, ?string $modelName = null): string
            {
                return '[' . ($modelName ?? $this->getModelName()) . '].[' . $field . ']';
            }

            public function getPrimaryKeyAttributes(?string $modelName = null): array
            {
                return $this->unitPrimaryKeyAttributes;
            }

            public function loadModel(?string $modelName = null): ModelInterface
            {
                return $this->unitModel ?? throw new \LogicException('No unit model configured.');
            }

            public function expose(mixed $item, ?array $expose = null): array
            {
                return ['exposed' => true];
            }
        };

        $controller->unitParams = $params;
        $controller->setDI($this->di);
        $controller->setEventsManager(new \Phalcon\Events\Manager());
        $controller->identity = new class {
            public function getUserId(): int
            {
                return 42;
            }

            public function hasRole(array|string $roles): bool
            {
                return false;
            }
        };

        return $controller;
    }

    private function newQueryController(array $params = []): Restful
    {
        $controller = new class extends Restful {
            public object $identity;
            /** @var array<string, mixed> */
            public array $unitParams = [];
            public ?ModelInterface $unitModel = null;
            public ?ModelInterface $unitFindFirstModel = null;
            public ?array $unitLastFind = null;
            public array $unitCreatedByColumns = ['createdBy'];
            public ?string $unitSoftDeleteColumn = 'deleted';
            public array $unitIdentityColumns = ['id'];
            public array|string|null $unitDefaultOrder = null;

            public function initialize(): void
            {
            }

            public function getParam(
                string $key,
                array|string|null $filters = null,
                mixed $default = null,
                ?array $params = null
            ): mixed {
                $params ??= $this->unitParams;

                return array_key_exists($key, $params) ? $params[$key] : $default;
            }

            public function getParams(?array $fields = null, bool $cached = true, bool $deep = true): array
            {
                return $this->unitParams;
            }

            public function hasParam(string $key, ?array $params = null, bool $cached = true): bool
            {
                $params ??= $this->unitParams;
                return array_key_exists($key, $params);
            }

            public function initializeDefaultOrder(): void
            {
                $this->setDefaultOrder($this->unitDefaultOrder);
            }

            public function getModelName(): ?string
            {
                return 'FooModel';
            }

            public function appendModelName(string $field, ?string $modelName = null): string
            {
                $field = trim($field);

                if (str_contains($field, '.')) {
                    [$alias, $column] = explode('.', $field, 2);
                    return '[' . $alias . '].[' . $column . ']';
                }

                return '[' . ($modelName ?? $this->getModelName()) . '].[' . $field . ']';
            }

            public function getPrimaryKeyAttributes(?string $modelName = null): array
            {
                return ['id'];
            }

            public function getCreatedByColumns(): array
            {
                return $this->unitCreatedByColumns;
            }

            public function getSoftDeleteColumn(): ?string
            {
                return $this->unitSoftDeleteColumn;
            }

            public function getIdentityColumns(): array
            {
                return $this->unitIdentityColumns;
            }

            public function loadModel(?string $modelName = null): ModelInterface
            {
                return $this->unitModel ?? throw new \LogicException('No unit model configured.');
            }

            public function findFirst(?array $find = null): ModelInterface|false|null
            {
                $this->unitLastFind = $find;
                return $this->unitFindFirstModel;
            }

            public function expose(mixed $item, ?array $expose = null): array
            {
                return ['exposed' => true];
            }

            public function exposeNormalizeJoins(array $joins): array
            {
                return $this->normalizeJoins($joins);
            }

            public function exposeNormalizeJoinPayload(array $payload, int|string $joinIndex = 0): array
            {
                return $this->normalizeJoinPayload($payload, $joinIndex);
            }

            public function exposeNormalizeDynamicJoinDefinition(string $alias, mixed $definition): array
            {
                return $this->normalizeDynamicJoinDefinition($alias, $definition);
            }

            public function exposeSetDynamicJoinState(array $mapping, array $build): void
            {
                $this->dynamicJoinsMapping = $mapping;
                $this->dynamicJoinsBuild = $build;
            }

            public function exposeMergeSqlConditions(string $a, string $b): string
            {
                return $this->mergeSqlConditions($a, $b);
            }

            public function exposeIsNegativeOperator(string $operator): bool
            {
                return $this->isNegativeOperator($operator);
            }

            public function exposeIsTextOperator(string $operator): bool
            {
                return $this->isTextOperator($operator);
            }

            public function exposeIsNegativeTextOperator(string $operator): bool
            {
                return $this->isNegativeTextOperator($operator);
            }

            public function exposeIsNoValueOperator(string $operator, bool $raw = true, bool $extended = true): bool
            {
                return $this->isNoValueOperator($operator, $raw, $extended);
            }

            public function exposeOptimizeOperatorAndValue(string $operator, mixed $value): array
            {
                return $this->optimizeOperatorAndValue($operator, $value);
            }

            public function exposeGetExistentialUniverseField(string $originalField): string
            {
                return $this->getExistentialUniverseField($originalField);
            }

            public function exposeAssembleLegacyGroupSql(array $fragments, int $level): string
            {
                return $this->assembleLegacyGroupSql($fragments, $level);
            }

            public function exposeSplitField(string $field): array
            {
                return $this->splitField($field);
            }

            public function exposeToPositiveOperator(string $operator): string
            {
                return $this->toPositiveOperator($operator);
            }

            public function exposeGetFilterScope(array $filter, ?string $aliasContext): string
            {
                return $this->getFilterScope($filter, $aliasContext);
            }

            public function exposeResolveGroupCarrierLogic(array $group): ?string
            {
                return $this->resolveGroupCarrierLogic($group);
            }

            public function exposeCompileSingleFilterCondition(
                string $fieldBinder,
                string $operator,
                array $filter,
                \Closure $getValue,
                string $mode = 'self'
            ): array {
                return $this->compileSingleFilterCondition($fieldBinder, $operator, $filter, $getValue, $mode);
            }

            public function exposeBuildExistsConditionFromField(
                string $field,
                string $condition,
                bool $negated = false
            ): array {
                return $this->buildExistsConditionFromField($field, $condition, $negated);
            }

            public function exposeGetExistentialBucketKey(string $originalField, bool $negated, string $scope): string
            {
                return $this->getExistentialBucketKey($originalField, $negated, $scope);
            }

            public function exposePushExistentialCondition(
                array &$pending,
                string $bucketKey,
                string $originalField,
                bool $negated,
                string $compiledConditionSql,
                array $bind,
                array $bindTypes
            ): void {
                $this->pushExistentialCondition(
                    $pending,
                    $bucketKey,
                    $originalField,
                    $negated,
                    $compiledConditionSql,
                    $bind,
                    $bindTypes
                );
            }

            public function exposeFlushExistentialBuckets(
                array &$pending,
                array &$fragments,
                array &$bind,
                array &$bindTypes
            ): void {
                $this->flushExistentialBuckets($pending, $fragments, $bind, $bindTypes);
            }

            public function exposeResolvePersistenceIntent(array $data, ?string $forceMode): array
            {
                return $this->resolvePersistenceIntent($data, $forceMode);
            }

            public function exposeAssignModelFromPayload(ModelInterface $model, array &$data): void
            {
                $this->assignModelFromPayload($model, $data);
            }

            public function exposePersistAssignedModel(ModelInterface $model, string $mode): array
            {
                return $this->persistAssignedModel($model, $mode);
            }

            public function exposeRequireResolvedPersistenceIntent(?string $mode, ?ModelInterface $model): array
            {
                return $this->requireResolvedPersistenceIntent($mode, $model);
            }

            public function exposeFindModelByIdentityPayload(array $payload): ?ModelInterface
            {
                return $this->findModelByIdentityPayload($payload);
            }

            public function exposeBuildRestSaveFailure(
                string $message,
                string $type,
                int $code,
                string|array|null $field = null
            ): array {
                return $this->buildRestSaveFailure($message, $type, $code, $field);
            }

            public function exposeHasPrimaryKey(array $data): bool
            {
                return $this->hasPrimaryKey($data);
            }

            public function exposeStripPrimaryKey(array &$data): void
            {
                $this->stripPrimaryKey($data);
            }

            public function exposeGetCalculationFind(?array $find = null, bool $removeLimitOffset = true): array
            {
                return $this->getCalculationFind($find, $removeLimitOffset);
            }

            public function exposePrepareFindListToString(array $items): string
            {
                return $this->prepareFindListToString($items);
            }
        };

        $controller->unitParams = $params;
        $controller->setDI($this->di);
        $controller->setEventsManager(new \Phalcon\Events\Manager());
        $controller->identity = new class {
            public function getUserId(): int
            {
                return 42;
            }

            public function hasRole(array|string $roles): bool
            {
                return false;
            }
        };

        return $controller;
    }
}
