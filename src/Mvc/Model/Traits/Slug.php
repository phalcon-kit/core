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

namespace PhalconKit\Mvc\Model\Traits;

use Phalcon\Mvc\EntityInterface;
use PhalconKit\Mvc\Model\Behavior\Transformable;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractBehavior;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractInjectable;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractOptions;

trait Slug
{
    use AbstractInjectable;
    use AbstractBehavior;
    use AbstractOptions;
    
    /**
     * Initializes the slug behavior for the model.
     *
     * @param array|null $options Optional. An array containing the options for the slug behavior. Default is null.
     * @return void
     */
    public function initializeSlug(?array $options = null): void
    {
        $options ??= $this->getOptionsManager()->get('slug') ?? [];
        
        $field = $options['field'] ?? 'slug';
        
        $this->setSlugBehavior(new Transformable([
            'beforeValidation' => [
                $field => function (EntityInterface $model, string $field) {
                    $value = $model->readAttribute($field);
                    return $value && is_string($value) ? \PhalconKit\Support\Slug::generate($value) : $value;
                },
            ],
        ]));
    }
    
    /**
     * Sets the slug behavior for the model.
     *
     * @param Transformable $slugBehavior A Transformable object representing the slug behavior.
     * @return void
     */
    public function setSlugBehavior(Transformable $slugBehavior): void
    {
        $this->setBehavior('slug', $slugBehavior);
    }
    
    /**
     * Returns the slug behavior associated with the model.
     *
     * @return Transformable The slug behavior associated with the model.
     */
    public function getSlugBehavior(): Transformable
    {
        return $this->getTypedBehavior('slug', Transformable::class);
    }
}
