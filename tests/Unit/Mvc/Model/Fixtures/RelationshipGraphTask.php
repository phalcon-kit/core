<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Model\Fixtures;

/** Real ORM has-many child whose label is required by the schema. */
final class RelationshipGraphTask extends \PhalconKit\Mvc\Model
{
    public mixed $id = null;
    public mixed $projectId = null;
    public mixed $label = null;

    #[\Override]
    public function initialize(): void
    {
        $this->setSource('graph_tasks');
        $this->keepSnapshots(true);
        $this->useDynamicUpdate(true);
    }
}
