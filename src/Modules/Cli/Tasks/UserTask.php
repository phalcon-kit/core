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

use PhalconKit\Modules\Cli\Task;
use PhalconKit\Modules\Cli\Tasks\Traits\UserTrait;

class UserTask extends Task
{
    use UserTrait;

    public string $cliDoc = <<<DOC
Usage:
  phalcon-kit cli user <action> [--password-stdin] [<params> ...]

Actions:
  create, password, role

Options:
  --password-stdin  Read the create/password secret from stdin instead of an argument.


DOC;
}
