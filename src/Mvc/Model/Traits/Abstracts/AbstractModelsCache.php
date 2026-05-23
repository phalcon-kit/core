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

namespace PhalconKit\Mvc\Model\Traits\Abstracts;

use Phalcon\Cache\Cache;
use PhalconKit\Exception\ServiceException;

/**
 * Shared typed accessor for the model cache service.
 *
 * Model traits use this helper when they are invoked through native Phalcon
 * model lifecycle hooks and only have access to the model's DI container. It
 * centralizes the PhalconKit DI contract check for the `modelsCache` service.
 */
trait AbstractModelsCache
{
    use AbstractInjectable;
    
    /**
     * Resolve the shared model cache service from the current model DI.
     *
     * @return Cache Cache service used by model cache invalidation helpers.
     * @throws ServiceException When the modelsCache service cannot be resolved
     *     through the PhalconKit DI contract.
     */
    public function getModelsCache(): Cache
    {
        return $this->getTypedService('modelsCache', Cache::class, 'model cache helpers');
    }
}
