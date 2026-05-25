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

namespace PhalconKit\Fractal;

/**
 * Framework-scoped League Fractal manager.
 *
 * This class currently keeps League Fractal's behavior unchanged. The wrapper
 * gives PhalconKit controllers, traits, and downstream applications a stable
 * framework type to depend on when configuring serializers, includes, and
 * transformers. Future framework-level defaults can be added here without
 * changing controller method signatures that already type against this manager.
 *
 * @see https://fractal.thephpleague.com/transformers/
 */
class Manager extends \League\Fractal\Manager
{
}
