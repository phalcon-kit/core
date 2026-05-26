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

namespace PhalconKit\Mvc\Model\Behavior;

use Phalcon\Mvc\Model\Behavior;
use Phalcon\Mvc\ModelInterface;
use PhalconKit\Exception\LogicException;
use PhalconKit\Mvc\Model\Behavior\Traits\SkippableTrait;

class Action extends Behavior
{
    use SkippableTrait;
    
    /**
     * @return void
     */
    #[\Override]
    public function notify(string $type, ModelInterface $model)
    {
        if (!$this->isEnabled()) {
            return;
        }
        
        if (!$this->mustTakeAction($type)) {
            return;
        }

        $options = $this->getOptions($type);
        if (empty($options)) {
            return;
        }

        foreach ($options as $action => $value) {
            if (!is_callable($value)) {
                throw new LogicException(sprintf(
                    'Action behavior option "%s" for event "%s" must be callable; got "%s".',
                    (string)$action,
                    $type,
                    get_debug_type($value)
                ));
            }

            $value($model, (string)$action);
        }
    }
}
