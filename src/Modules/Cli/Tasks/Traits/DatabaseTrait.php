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

namespace PhalconKit\Modules\Cli\Tasks\Traits;

use Phalcon\Config\Exception as ConfigException;
use Phalcon\Mvc\Model;
use PhalconKit\Exception\CliException;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractInjectable;
use PhalconKit\Mvc\Model\Interfaces\HashInterface;

trait DatabaseTrait
{
    use AbstractInjectable;
    
    public array $drop = [];
    public array $truncate = [];
    public array $engine = [];
    public array $insert = [];
    public array $optimize = [];
    public array $analyze = [];
    
    /**
     * Default action
     */
    public function mainAction(): ?array
    {
        $response = [];
        
        $response ['engine'] = $this->fixEngineAction();
        $response ['optimize'] = $this->optimizeAction();
        $response ['analyze'] = $this->analyzeAction();
        
        return $response;
    }
    
    /**
     * The resetAction method is responsible for resetting the state of the application by performing
     * the following actions:
     *
     * 1. Truncate database tables using the truncateAction method.
     * 2. Insert initial data into the database using the insertAction method.
     *
     * Use Case:
     *
     * This method can be used when you need to reset the state of the application to its initial state.
     * It is commonly used for testing or when you want to re-populate the database with initial data.
     *
     * @return array
     * @throws CliException
     */
    public function resetAction(): array
    {
        $response = [];
        
        $response ['truncate'] = $this->truncateAction();
        $response ['insert'] = $this->insertAction();
        
        return $response;
    }
    
    /**
     * The truncateAction method is responsible for truncating (emptying) database tables specified in the
     * $this->truncate array. Truncating a table removes all of its data, effectively resetting it to an
     * empty state. This method iterates through a list of table names and executes an SQL TRUNCATE TABLE
     * command for each of them.
     *
     * Use Case:
     * This method is often used when you need to reset the data in database tables without deleting
     * the table itself. Truncating tables is a quicker alternative to deleting all rows one by one.
     */
    public function truncateAction(): array
    {
        $response = [];
        
        foreach ($this->truncate as $table) {
            $response [$table] = $this->db->execute('TRUNCATE TABLE ' . $this->db->escapeIdentifier($table));
        }
        
        return $response;
    }
    
    /**
     * The dropAction method is responsible for dropping database tables specified in the $this->drop array.
     * Dropping a table means permanently removing it from the database schema. This method iterates through
     * a list of table names and executes an SQL DROP TABLE command for each of them, with a safety check to
     * ensure that the table is only dropped if it exists.
     *
     * Use Case:
     * This method is commonly used when performing database schema changes or cleanup tasks, where you need
     * to remove tables that are no longer needed. The IF EXISTS clause is a safety measure to prevent
     * accidental deletion of tables.
     */
    public function dropAction(): array
    {
        $response = [];
        
        foreach ($this->drop as $table) {
            $response [$table] = $this->db->execute('DROP TABLE IF EXISTS ' . $this->db->escapeIdentifier($table));
        }
        
        return $response;
    }
    
    /**
     * The fixEngineAction method is responsible for fixing or changing the storage engine for database tables
     * specified in the $this->engine array. A storage engine determines how data is stored and managed within
     * a database table. This method iterates through a list of table names and their corresponding desired
     * storage engines and executes SQL ALTER TABLE commands to make the necessary changes.
     *
     * Use Case:
     * This method is useful when you need to adjust the storage engine of database tables to optimize performance,
     * compatibility, or for other specific requirements. Different storage engines have different characteristics,
     * and choosing the right one can impact table performance and functionality.
     */
    public function fixEngineAction(): array
    {
        $response = [];
        
        foreach ($this->engine as $table => $engine) {
            $response [$table] = $this->db->execute('ALTER TABLE ' . $this->db->escapeIdentifier($table) . ' ENGINE = ' . $engine) ? $engine : false;
        }
        
        return $response;
    }
    
    /**
     * Insert records
     * @throws CliException
     */
    public function insertAction(?string $models = null): array
    {
        $response = [
            'saved' => 0,
            'error' => [],
            'message' => [],
        ];
        
        $models = (!empty($models)) ? explode(',', $models) : null;
        
        foreach ($this->insert as $modelName => $insert) {
            if (is_array($models) && !in_array($modelName, $models, true)) {
                continue;
            }
            
            foreach ($insert as $key => $row) {
                assert(is_string($modelName) && class_exists($modelName));
                $entity = new $modelName();
                assert($entity instanceof Model);
                
                $assign = isset($row[0]) && method_exists($entity, 'columnMap')
                    ? array_combine($entity->columnMap(), $row)
                    : $row;
                
                if (!$assign) {
                    throw new CliException('Can\'t assign row #' . $key . ' for model `' . $modelName . '`.');
                }
                else {
                    $entity->assign($assign);
                }
                
                // Automagically fill passwords
                if (property_exists($entity, 'password')) {
                    if (empty($row['password']) && property_exists($entity, 'email')) {
                        if (method_exists($entity, 'hash')) {
                            $entity->assign(['password' => $entity->hash($row['email'])]);
                        }
                    }
                }
                
                try {
                    if (!$entity->save()) {
                        $response['error'][$modelName][] = $entity->toArray();
                        
                        foreach ($entity->getMessages() as $message) {
                            $response['message'][$modelName][] = $message;
                        }
                    }
                    else {
                        $response['saved']++;
                    }
                } catch (\Exception $e) {
                    $response['error'][$modelName][] = $entity->toArray();
                    $response['message'][$modelName][] = $e->getMessage();
                }
            }
        }
        
        return $response;
    }
    
    /**
     * The optimizeAction method is responsible for optimizing database tables specified in the
     * $this->optimize array. Database table optimization is a maintenance task aimed at improving
     * the performance and storage efficiency of database tables. This method iterates through a
     * list of table names and executes an SQL OPTIMIZE TABLE command for each of them.
     *
     * Use Case:
     * This method is typically used in the context of database maintenance and optimization routines.
     * It allows you to automate the process of optimizing database tables, which can help reclaim storage
     * space and improve query performance by reorganizing table data and indexes.
     */
    public function optimizeAction(): array
    {
        $response = [];
        
        foreach ($this->optimize as $table) {
            $response [$table] = $this->db->query('OPTIMIZE TABLE ' . $this->db->escapeIdentifier($table))->fetchAll();
        }
        
        return $response;
    }
    
    /**
     * This method is responsible for analyzing database tables specified in the $this->analyse array.
     * Table analysis is an essential database maintenance task that helps optimize the performance
     * of database queries. Analyzing a table refreshes statistics and metadata about the table's
     * structure, which can lead to improved query execution plans.
     *
     * Use Case:
     * This method can be used in the context of database optimization and maintenance scripts.
     * It allows you to automate the process of analyzing database tables, ensuring that the database's
     * query optimizer has up-to-date statistics to make informed decisions about query execution plans.
     */
    public function analyzeAction(): array
    {
        $response = [];
        
        foreach ($this->analyze as $table) {
            $response [$table] = $this->db->query('ANALYZE TABLE ' . $this->db->escapeIdentifier($table))->fetchAll();
        }
        
        return $response;
    }
    
    /**
     * @throws ConfigException When permission configuration cannot be merged.
     */
    public function addModelsPermissions(?array $tables = null): void
    {
        $permissions = [];
        $tables ??= $this->insert;
        foreach ($tables as $model => $entity) {
            $permissions[$model] = ['*'];
        }
        
        $this->config->merge([
            'permissions' => [
                'roles' => [
                    'cli' => [
                        'models' => $permissions,
                    ],
                ],
            ],
        ], true);
        $this->acl->setOption('permissions', $this->config->pathToArray('permissions') ?? []);
    }
}
