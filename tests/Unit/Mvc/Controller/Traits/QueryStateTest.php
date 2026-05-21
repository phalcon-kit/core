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

use Phalcon\Support\Collection;
use PhalconKit\Mvc\Controller\Restful;
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

    public function testFieldCollectionsTrackPresenceAndMergeState(): void
    {
        $controller = $this->newQueryController();

        $controller->initializeExposeFields();
        $controller->initializeFilterFields();
        $controller->initializeMapFields();
        $controller->initializeSaveFields();
        $controller->initializeSearchFields();

        $this->assertFalse($controller->hasExposeFields());
        $this->assertNull($controller->getFilterFields());
        $this->assertNull($controller->getMapFields());
        $this->assertNull($controller->getSaveFields());
        $this->assertNull($controller->getSearchFields());

        $controller->mergeExposeFields(new Collection(['id']));
        $controller->mergeFilterFields(new Collection(['status']));
        $controller->mergeMapFields(new Collection(['publicName' => 'name']));
        $controller->mergeSaveFields(new Collection(['title']));
        $controller->mergeSearchFields(new Collection(['body']));

        $this->assertTrue($controller->hasExposeFields());
        $this->assertSame(['id'], $controller->getExposeFields()?->toArray());
        $this->assertSame(['status'], $controller->getFilterFields()?->toArray());
        $this->assertSame(['publicName' => 'name'], $controller->getMapFields()?->toArray());
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

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('must be higher or equal to -1');

        $controller->setLimit(-2);
    }

    public function testLimitRejectsValuesAboveTheConfiguredMaximum(): void
    {
        $controller = $this->newQueryController();
        $controller->setMaxLimit(5);

        $this->expectException(\Exception::class);
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

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('must be higher than or equal to 0');

        $controller->setOffset(-1);
    }

    private function newQueryController(array $params = []): Restful
    {
        $controller = new class extends Restful {
            public object $identity;
            /** @var array<string, mixed> */
            public array $unitParams = [];

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
                return ['id'];
            }
        };

        $controller->unitParams = $params;
        $controller->identity = new class {
            public function getUserId(): int
            {
                return 42;
            }
        };

        return $controller;
    }
}
