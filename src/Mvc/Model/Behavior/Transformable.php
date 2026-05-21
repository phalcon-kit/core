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

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model\Behavior;
use Phalcon\Mvc\ModelInterface;
use PhalconKit\Mvc\Model\Behavior\Traits\SkippableTrait;

/**
 * PhalconKit\Mvc\Model\Traits\Behavior\Transformable
 *
 * Allows to automatically update a model’s attribute saving the datetime when a
 * record is created or updated
 */
class Transformable extends Behavior
{
    use SkippableTrait;
    
    /**
     * Listens for notifications from the models manager
     */
    #[\Override]
    public function notify(string $type, ModelInterface $model): ?bool
    {
        if (!$this->isEnabled()) {
            return null;
        }
        
        if (!$this->mustTakeAction($type)) {
            return null;
        }

        $options = $this->getOptions($type);
        if (empty($options)) {
            return null;
        }

        foreach ($options as $field => $value) {
            if (!property_exists($model, $field)) {
                continue;
            }
            
            $value = ($value instanceof \Closure || (is_object($value) && is_callable($value)))
                ? $value($model, $field)
                : $value;
            
            // allow up to 10 callbacks
            $limit = 10;
            while (($value instanceof \Closure || (is_object($value) && is_callable($value))) && --$limit) {
                $value = $value();
            }
            
            assert($model instanceof EntityInterface);
            $model->writeAttribute($field, $value);
//            $model->assign([$field => $value]);
        }
        
        return true;
    }
}
