<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Model\Fixtures;

/** Real ORM parent with a has-many and a many-to-many relation for persistence tests. */
final class RelationshipGraphProject extends \PhalconKit\Mvc\Model
{
    public mixed $id = null;
    public mixed $name = null;

    #[\Override]
    public function initialize(): void
    {
        $this->setSource('graph_projects');
        $this->keepSnapshots(true);
        $this->useDynamicUpdate(true);
        $this->hasMany('id', RelationshipGraphTask::class, 'projectId', ['alias' => 'tasks']);
        $this->hasManyToMany(
            'id',
            RelationshipGraphProjectTag::class,
            'projectId',
            'tagId',
            RelationshipGraphTag::class,
            'id',
            ['alias' => 'tags']
        );
    }
}
