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

namespace PhalconKit\Tests\Unit\Mvc\Model\Fixtures;

use Phalcon\Messages\Message;
use Phalcon\Mvc\Model\Resultset\Simple;

class FailingModelResultsetDouble extends Simple
{
    public function __construct(
        private readonly bool $deleteResult = false,
        private readonly int $rowCount = 1
    ) {
        parent::__construct(null, new ModelBehaviorDouble(), new DbResultDouble($this->rowCount));
    }

    #[\Override]
    public function delete(?\Closure $conditionCallback = null): bool
    {
        return $this->deleteResult;
    }

    #[\Override]
    public function getMessages(): array
    {
        return [new Message('Resultset delete failed', 'id')];
    }
}
