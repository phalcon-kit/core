<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Model\Fixtures;

/** Real ORM fixture with an owned relation using two reference columns. */
final class RelationshipSecurityParent extends \PhalconKit\Mvc\Model
{
    public mixed $tenantId = null;
    public mixed $id = null;
    public mixed $name = null;

    public function initialize(): void
    {
        $this->setSource('security_parents');
        $this->keepSnapshots(true);
        $this->useDynamicUpdate(true);
        $this->hasOne(['tenantId', 'id'], RelationshipSecurityChild::class, ['parentTenantId', 'parentId'], ['alias' => 'child']);
        $this->hasMany(['tenantId', 'id'], RelationshipSecurityChild::class, ['parentTenantId', 'parentId'], ['alias' => 'children']);
    }
}
