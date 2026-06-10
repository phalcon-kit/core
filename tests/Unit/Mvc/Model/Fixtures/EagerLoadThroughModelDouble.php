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

use PhalconKit\Mvc\Model;

class EagerLoadThroughModelDouble extends Model
{
    public mixed $id = null;
    public mixed $parentId = null;
    public mixed $targetId = null;
    public mixed $deleted = 0;

    #[\Override]
    public function initialize(): void
    {
        parent::initialize();
        $this->setSource('eager_load_through_model_double');
    }
}
