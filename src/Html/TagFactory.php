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

namespace PhalconKit\Html;

use Phalcon\Html\TagFactory as PhalconTagFactory;

/**
 * Framework HTML tag factory.
 *
 * This class intentionally keeps Phalcon's tag-factory behavior while giving
 * applications a PhalconKit-scoped type to register in the DI container. Use it
 * when a service needs native Phalcon tag helpers but should remain typed to a
 * framework-owned class.
 *
 * Applications that need to replace or decorate HTML helpers can extend this
 * class and keep existing consumers pointed at the same `tag`/tag-factory
 * service boundary.
 *
 * @see PhalconTagFactory
 */
class TagFactory extends PhalconTagFactory
{
}
