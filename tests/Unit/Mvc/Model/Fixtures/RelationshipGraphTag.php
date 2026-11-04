<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Model\Fixtures;

/** Real ORM many-to-many target shared between projects. */
final class RelationshipGraphTag extends \PhalconKit\Mvc\Model
{
    public mixed $id = null;
    public mixed $name = null;

    #[\Override]
    public function initialize(): void
    {
        $this->setSource('graph_tags');
        $this->keepSnapshots(true);
        $this->useDynamicUpdate(true);
    }
}
