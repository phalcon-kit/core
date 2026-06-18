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
use PhalconKit\Exception\LogicException;
use PhalconKit\Mvc\Model\Behavior\Traits\SkippableTrait;

/**
 * Applies configured attribute transformations during model lifecycle events.
 *
 * Each watched event can define a field-to-value map. Values may be scalars or
 * callbacks; callbacks receive the model and field name on the first pass and
 * may return another callback for deferred value generation. The final value is
 * written through Phalcon's entity API so column maps and model internals stay
 * consistent.
 *
 * @see https://docs.phalcon.io/5.15/db-models-events/
 */
class Transformable extends Behavior
{
    use SkippableTrait;
    
    /**
     * Handle a model manager lifecycle notification.
     *
     * @param string $type Event name emitted by Phalcon's model manager.
     * @param ModelInterface $model Model receiving transformed values.
     *
     * @return bool|null True when a configured transformation ran, null when
     *     the behavior is disabled, does not match the event, or has no work.
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
            
            if (!$model instanceof EntityInterface) {
                throw new LogicException(sprintf(
                    'Transformable behavior for event "%s" requires a model implementing "%s"; got "%s".',
                    $type,
                    EntityInterface::class,
                    get_debug_type($model)
                ));
            }

            $model->writeAttribute($field, $value);
        }
        
        return true;
    }
}
