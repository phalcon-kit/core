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

namespace PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures;

use Phalcon\Di\Injectable;
use Phalcon\Events\Manager;

class InjectableBehaviorTestListener extends Injectable
{
    public string $eventType = 'custom';

    public int $priority = Manager::DEFAULT_PRIORITY + 10;
}
