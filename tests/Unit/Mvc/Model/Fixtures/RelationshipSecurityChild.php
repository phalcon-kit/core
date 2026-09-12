<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Model\Fixtures;

/** Real ORM fixture; persistence is confined to the opt-in disposable schema. */
final class RelationshipSecurityChild extends \PhalconKit\Mvc\Model
{
    public mixed $id = null;
    public mixed $parentTenantId = null;
    public mixed $parentId = null;
    public mixed $name = null;

    public function initialize(): void
    {
        $this->setSource('security_children');
        $this->keepSnapshots(true);
        $this->useDynamicUpdate(true);
        $this->belongsTo(['parentTenantId', 'parentId'], RelationshipSecurityParent::class, ['tenantId', 'id'], ['alias' => 'owner']);
    }
}
