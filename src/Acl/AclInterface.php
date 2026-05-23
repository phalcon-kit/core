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

namespace PhalconKit\Acl;

use Phalcon\Acl\Adapter\Memory;
use PhalconKit\Support\Options\OptionsInterface;

/**
 * Contract for ACL builders backed by PhalconKit permission arrays.
 *
 * Implementations compile framework/application permission config into a native
 * Phalcon ACL object. The interface extends `OptionsInterface` so services can
 * be configured with a default permission tree and reused by controllers,
 * identity services, or tests.
 */
interface AclInterface extends OptionsInterface
{
    /**
     * Build an in-memory ACL from one or more permission component sections.
     *
     * @param array<int, string> $componentsName Permission sections to include.
     * @param array<string, mixed>|null $permissions Permission tree to compile,
     *     or null to use the service options.
     * @param string $inherit Role-inheritance key.
     */
    public function get(array $componentsName = ['components'], ?array $permissions = null, string $inherit = 'inherit'): Memory;
}
