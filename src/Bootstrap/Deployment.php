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

namespace PhalconKit\Bootstrap;

use Phalcon\Config\Config as PhalconConfig;

/**
 * Application-owned instructions for the database maintenance task.
 *
 * All operations are empty by default. Configure `deployment` on the shared
 * config service, or set the corresponding arrays on an application's
 * DatabaseTask. Core does not assume a schema, truncate tables, or create
 * accounts when no instructions have been supplied.
 *
 * @property PhalconConfig $drop Table names to drop, including their data.
 * @property PhalconConfig $truncate Table names to empty.
 * @property PhalconConfig $engine Map of table names to trusted engine names.
 * @property PhalconConfig $insert Map of model class names to seed row arrays.
 * @property PhalconConfig $optimize Table names to optimize.
 * @property PhalconConfig $analyze Table names to analyze.
 */
class Deployment extends \PhalconKit\Config\Config
{
    /**
     * Normalize application instructions without adding tables or seed records.
     *
     * This constructor performs no database operations. Seed keys must name the
     * concrete model to instantiate; model mappings are not applied by the task.
     *
     * @param array<string, mixed> $data Explicit maintenance lists and seed data.
     * @param bool $insensitive Whether config keys should be case-insensitive.
     */
    public function __construct(array $data = [], bool $insensitive = true)
    {
        parent::__construct($this->internalMergeAppend([
            'drop' => [],
            'truncate' => [],
            'engine' => [],
            'insert' => [],
            'optimize' => [],
            'analyze' => [],
        ], $data), $insensitive);
    }
}
