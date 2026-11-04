<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Model\Fixtures;

/** Real ORM link row between a project and a tag, with Core's soft-delete behavior. */
final class RelationshipGraphProjectTag extends \PhalconKit\Mvc\Model
{
    public mixed $id = null;
    public mixed $projectId = null;
    public mixed $tagId = null;
    public mixed $deleted = 0;

    #[\Override]
    public function initialize(): void
    {
        $this->initializeOptions();
        $this->initializeSoftDelete();
        $this->setSource('graph_project_tags');
        $this->keepSnapshots(true);
        $this->useDynamicUpdate(true);
    }
}
