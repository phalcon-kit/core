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

namespace PhalconKit\Modules\Cli\Tasks;

use Phalcon\Config\Exception as ConfigException;
use PhalconKit\Bootstrap\Deployment;
use PhalconKit\Modules\Cli\Task;
use PhalconKit\Modules\Cli\Tasks\Traits\DatabaseTrait;
use PhalconKit\Support\Utils;

class DatabaseTask extends Task
{
    use DatabaseTrait;
    
    public string $cliDoc = <<<DOC
Usage:
  phalcon-kit cli database main
  phalcon-kit cli database drop
  phalcon-kit cli database truncate
  phalcon-kit cli database fix-engine
  phalcon-kit cli database optimize
  phalcon-kit cli database analyze
  phalcon-kit cli database insert

Options:
  main:         truncate, drop, fix-engine, insert, optimize, analyze
  drop:         Drop deprecated tables
  truncate:     Truncate tables
  fix-engine:   Force table engines to `InnoDB`
  optimize:     Run `OPTIMIZE TABLE`
  analyze:      Run `ANALYZE TABLE`
  insert:       Insert records
DOC;
    
    /**
     * @throws ConfigException When deployment configuration cannot be read.
     */
    public function initialize(): void
    {
        Utils::setUnlimitedRuntime();
        
        $deploymentConfig = new Deployment();
        $this->drop = $deploymentConfig->pathToArray('drop') ?? [];
        $this->truncate = $deploymentConfig->pathToArray('truncate') ?? [];
        $this->engine = $deploymentConfig->pathToArray('engine') ?? [];
        $this->insert = $deploymentConfig->pathToArray('insert') ?? [];
        $this->optimize = $deploymentConfig->pathToArray('optimize') ?? [];
        $this->analyze = $deploymentConfig->pathToArray('analyze') ?? [];
        
        $this->addModelsPermissions();
    }
}
