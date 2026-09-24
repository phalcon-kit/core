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

/**
 * Execute application-defined database maintenance and seed operations.
 *
 * Configure the shared `config` service's `deployment` section or override the
 * task's public instruction arrays. Only listed tables/models are processed;
 * an unconfigured task performs no database queries. Execution requires `db`
 * for SQL operations and the normal model services for seed records.
 */
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
  phalcon-kit cli database reset

Options:
  main:         fix-engine, optimize, analyze
  drop:         Drop deprecated tables
  truncate:     Truncate tables
  fix-engine:   Apply configured table engines
  optimize:     Run `OPTIMIZE TABLE`
  analyze:      Run `ANALYZE TABLE`
  insert:       Insert records
  reset:        Truncate configured tables, then insert configured records

Table lists and seed records come from application deployment configuration.
All operations are empty by default.
DOC;
    
    /**
     * Load explicit deployment instructions and grant CLI access to seed models.
     *
     * Values in `config.deployment` replace the matching task property in full.
     * Omitted keys preserve subclass defaults, including values set before
     * `parent::initialize()`. An explicit empty array disables that operation.
     * Requires the shared `config` and `acl` services; no database is opened here.
     * The CLI time and memory limits are removed before running maintenance.
     *
     * @throws ConfigException When deployment configuration cannot be read.
     */
    public function initialize(): void
    {
        Utils::setUnlimitedRuntime();
        
        $deploymentConfig = new Deployment(array_replace([
            'drop' => $this->drop,
            'truncate' => $this->truncate,
            'engine' => $this->engine,
            'insert' => $this->insert,
            'optimize' => $this->optimize,
            'analyze' => $this->analyze,
        ], $this->config->pathToArray('deployment') ?? []));
        $this->drop = $deploymentConfig->pathToArray('drop') ?? [];
        $this->truncate = $deploymentConfig->pathToArray('truncate') ?? [];
        $this->engine = $deploymentConfig->pathToArray('engine') ?? [];
        $this->insert = $deploymentConfig->pathToArray('insert') ?? [];
        $this->optimize = $deploymentConfig->pathToArray('optimize') ?? [];
        $this->analyze = $deploymentConfig->pathToArray('analyze') ?? [];
        
        $this->addModelsPermissions();
    }
}
