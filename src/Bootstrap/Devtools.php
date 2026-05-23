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

use PhalconKit\Exception\ConfigurationException;

/**
 * Config adapter shape expected by Phalcon DevTools.
 *
 * PhalconKit's normal database config supports named drivers and adapter class
 * names. DevTools expects a flatter database array with a short adapter name,
 * so this config extracts the selected driver and normalizes it for DevTools
 * commands.
 */
class Devtools extends Config
{
    /**
     * Build a DevTools-compatible database config from PhalconKit config data.
     *
     * @param array<string, mixed> $data Application config overrides.
     * @param bool $insensitive Whether config keys should be case-insensitive.
     *
     * @throws ConfigurationException When the selected database driver does not
     *     define a valid adapter class.
     */
    public function __construct(array $data = [], bool $insensitive = true)
    {
        parent::__construct($data, $insensitive);
    
        $databaseConfig = $this->pathToArray('database');
        $driverName = $databaseConfig['default'] ?? 'mysql';
        $driverOptions = $databaseConfig['drivers'][$driverName] ?? [];
        $adapterClass = $driverOptions['adapter'] ?? null;
        
        if (!isset($adapterClass) || !is_string($adapterClass)) {
            throw new ConfigurationException('A valid database adapter class must be provided.');
        }
    
        $driverOptions['adapter'] = basename(str_replace('\\', '/', $adapterClass));
        unset($driverOptions['readonly']);
        
        $this->set('database', $driverOptions);
    }
}
