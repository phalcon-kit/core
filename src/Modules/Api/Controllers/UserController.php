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

namespace PhalconKit\Modules\Api\Controllers;

use PhalconKit\Modules\Api\Controller;

class UserController extends Controller
{
    #[\Override]
    public function initializeWith(): void
    {
        $this->setWith([
            'RoleList',
        ]);
    }
    
    #[\Override]
    public function initializeSearchFields(): void
    {
        $this->setSearchFields([
            'id',
            'email',
            'firstName',
            'lastName',
        ]);
    }
    
    #[\Override]
    public function initializeExposeFields(): void
    {
        $this->setExposeFields([
            true,
            'password' => false,
        ]);
    }
}
