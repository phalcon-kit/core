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
use PhalconKit\Models\Lang;
use PhalconKit\Models\Role;
use PhalconKit\Models\Setting;
use PhalconKit\Models\Template;
use PhalconKit\Models\User;
use PhalconKit\Models\UserRole;
use PhalconKit\Models\Workspace;

/**
 * Default database deployment/scaffolding configuration.
 *
 * This config drives maintenance tasks that prepare a fresh PhalconKit
 * database: tables to drop, tables to truncate, storage engines, and seed
 * records for core lookup/user tables. Applications can merge additional data
 * into the constructor without editing the framework defaults.
 *
 * @property PhalconConfig $drop
 * @property PhalconConfig $truncate
 * @property PhalconConfig $engine
 * @property PhalconConfig $insert
 */
class Deployment extends \PhalconKit\Config\Config
{
    /**
     * Merge default deployment instructions with application overrides.
     *
     * @param array<string, mixed> $data Deployment overrides or additional seed
     *     records.
     * @param bool $insensitive Whether config keys should be case-insensitive.
     */
    public function __construct(array $data = [], bool $insensitive = true)
    {
        $data = $this->internalMergeAppend([
            /**
             * Tables to drop
             */
            'drop' => [],
            
            /**
             * Tables to truncate
             */
            'truncate' => [
                'audit',
                'audit_detail',
                'category',
                'table',
                'data',
                'email',
                'email_file',
                'column',
                'file',
                'file_relation',
                'flag',
                'group',
                'group_role',
                'group_type',
                'lang',
                'log',
                'menu',
                'meta',
                'page',
                'phalcon_migrations',
                'post',
                'post_category',
                'record',
                'role',
                'session',
                'setting',
                'workspace',
                'workspace_lang',
                'template',
                'translate',
                'type',
                'user',
                'user_group',
                'user_role',
                'user_type',
                'validator',
            ],
            
            /**
             * Table engines
             */
            'engine' => [
                'audit' => 'InnoDB',
                'audit_detail' => 'InnoDB',
                'category' => 'InnoDB',
                'table' => 'InnoDB',
                'data' => 'InnoDB',
                'email' => 'InnoDB',
                'email_file' => 'InnoDB',
                'column' => 'InnoDB',
                'file' => 'InnoDB',
                'file_relation' => 'InnoDB',
                'flag' => 'InnoDB',
                'group' => 'InnoDB',
                'group_role' => 'InnoDB',
                'group_type' => 'InnoDB',
                'lang' => 'InnoDB',
                'log' => 'InnoDB',
                'menu' => 'InnoDB',
                'meta' => 'InnoDB',
                'page' => 'InnoDB',
                'phalcon_migrations' => 'InnoDB',
                'post' => 'InnoDB',
                'post_category' => 'InnoDB',
                'role' => 'InnoDB',
                'session' => 'InnoDB',
                'setting' => 'InnoDB',
                'workspace' => 'InnoDB',
                'workspace_lang' => 'InnoDB',
                'template' => 'InnoDB',
                'translate' => 'InnoDB',
                'type' => 'InnoDB',
                'user' => 'InnoDB',
                'user_group' => 'InnoDB',
                'user_role' => 'InnoDB',
                'user_type' => 'InnoDB',
                'validator' => 'InnoDB',
            ],
            
            /**
             * Insert records
             */
            'insert' => [
                UserRole::class => [],
                Role::class => [
                    ['key' => 'dev', 'label' => 'Developer'],
                    ['key' => 'admin', 'label' => 'Administrator'],
                    ['key' => 'user', 'label' => 'User'],
                    ['key' => 'guest', 'label' => 'Guest'],
                    ['key' => 'everyone', 'label' => 'Everyone'],
                ],
                User::class => [
                    ['username' => 'dev', 'email' => 'dev@localhost', 'firstName' => 'Developer', 'lastName' => 'Phalcon Kit', 'rolelist' => [1]],
                ],
                Lang::class => [
                    ['label' => 'Francais', 'code' => 'fr'],
                    ['label' => 'English', 'code' => 'en'],
                    ['label' => 'Spanish', 'code' => 'sp'],
                ],
                Workspace::class => [
                ],
                Template::class => [
                ],
                Setting::class => [
                ],
            ],
        ], $data);
        
        parent::__construct($data, $insensitive);
    }
}
