<?php

declare(strict_types=1);

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace PhalconKit\Mvc\Controller\Behavior\Model;

/**
 * Reserved controller behavior marker for delete-oriented model workflows.
 *
 * The class intentionally has no default listener methods. It gives
 * applications a stable PhalconKit behavior name they can extend, replace, or
 * attach to event configuration when delete-specific model hooks are needed.
 */
class Delete
{
}
