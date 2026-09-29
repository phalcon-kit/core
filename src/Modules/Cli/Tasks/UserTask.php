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

    /**
     * Parse account options and pass only positional arguments to action methods.
     *
     * Bootstrap initially retains task flags in its variadic argument list.
     * Replace those numeric entries with Docopt's parsed params so secret-input
     * flags cannot be mistaken for the password itself. Named task options stay
     * available through the dispatcher.
     */
    #[\Override]
    public function beforeExecuteRoute(): void
    {
        parent::beforeExecuteRoute();
        $params = $this->dispatcher->getParameter('params') ?? [];
        $options = array_filter($this->dispatcher->getParameters(), 'is_string', ARRAY_FILTER_USE_KEY);
        $this->dispatcher->setParameters(array_merge($params, $options));
    }
    
    public string $cliDoc = <<<DOC
Usage:
  phalcon-kit cli user <action> [--password-stdin] [<params> ...]

Options:
  task: user
  action: create, password, role
  --password-stdin  Read the create/password secret from stdin instead of an argument.


DOC;
}
