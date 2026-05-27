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
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\FieldPolicyControllerDouble;

final class FieldPolicyTest extends AbstractUnit
{
    public function testFieldPoliciesInitializeAsUnrestrictedOrUnset(): void
    {
        $controller = new FieldPolicyControllerDouble();
        $controller->initializeExposeFields();
        $controller->initializeFilterFields();
        $controller->initializeMapFields();
        $controller->initializeOrderFields();
        $controller->initializeSaveFields();
        $controller->initializeSearchFields();

        foreach ($this->fieldPolicyMethods() as [$getter, , $has]) {
            $this->assertNull($controller->{$getter}(), $getter);
            $this->assertFalse($controller->{$has}(), $has);
        }
    }

    public function testFieldPoliciesPreserveEmptyCollectionsAsExplicitPolicies(): void
    {
        $controller = new FieldPolicyControllerDouble();

        foreach ($this->fieldPolicyMethods() as [$getter, $setter, $has]) {
            $policy = new Collection([], false);
            $controller->{$setter}($policy);

            $this->assertSame($policy, $controller->{$getter}(), $getter);
            $this->assertTrue($controller->{$has}(), $has);
            $this->assertSame([], $controller->{$getter}()?->toArray(), $getter);
        }
    }

    public function testFieldPoliciesShareNullableMergeSemantics(): void
    {
        $controller = new FieldPolicyControllerDouble();

        foreach ($this->fieldPolicyMethods() as [$getter, , , $merge]) {
            $controller->{$merge}(new Collection(['id'], false));
            $this->assertSame(['id'], $controller->{$getter}()?->toArray(), $getter);

            $controller->{$merge}(new Collection([], false));
            $this->assertSame(['id'], $controller->{$getter}()?->toArray(), $getter);

            $controller->{$merge}(new Collection(['status' => 'state'], false));
            $this->assertSame([
                'id',
                'status' => 'state',
            ], $controller->{$getter}()?->toArray(), $getter);
        }
    }

    public function testOrderFieldMapNormalizesPolicyShapes(): void
    {
        $controller = new FieldPolicyControllerDouble();
        $controller->setOrderFields(new Collection([
            'createdAt',
            'ownerEmail' => 'Owner.email',
            'status' => true,
            'disabled' => false,
            'blank' => '',
            'null' => null,
        ], false));

        $this->assertSame([
            'createdAt' => 'createdAt',
            'ownerEmail' => 'Owner.email',
            'status' => 'status',
        ], $controller->exposeOrderFieldMap());
    }

    public function testOrderFieldStringMapValuesRemainAliasesNotEnabledFlags(): void
    {
        $controller = new FieldPolicyControllerDouble();
        $controller->setOrderFields(new Collection([
            'legacySort' => 'off',
            'archivedSort' => 'false',
        ], false));

        $this->assertSame([
            'legacySort' => 'off',
            'archivedSort' => 'false',
        ], $controller->exposeOrderFieldMap());
    }

    /**
     * Return the common getter, setter, presence, and merge method names.
     *
     * @return list<array{0: string, 1: string, 2: string, 3: string}>
     */
    private function fieldPolicyMethods(): array
    {
        return [
            ['getExposeFields', 'setExposeFields', 'hasExposeFields', 'mergeExposeFields'],
            ['getFilterFields', 'setFilterFields', 'hasFilterFields', 'mergeFilterFields'],
            ['getMapFields', 'setMapFields', 'hasMapFields', 'mergeMapFields'],
            ['getOrderFields', 'setOrderFields', 'hasOrderFields', 'mergeOrderFields'],
            ['getSaveFields', 'setSaveFields', 'hasSaveFields', 'mergeSaveFields'],
            ['getSearchFields', 'setSearchFields', 'hasSearchFields', 'mergeSearchFields'],
        ];
    }
}
