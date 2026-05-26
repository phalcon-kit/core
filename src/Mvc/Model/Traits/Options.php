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

use PhalconKit\Exception\LogicException;
use PhalconKit\Support\Options\Manager;
use PhalconKit\Support\Options\ManagerInterface;

/**
 * The Options trait provides methods for managing options using an options manager.
 */
trait Options
{
    public ?ManagerInterface $optionsManager = null;
    
    /**
     * Initialize the Options Manager for the current instance
     *
     * @return void
     */
    public function initializeOptions(): void
    {
        $manager = new Manager();
        $this->setOptionsManager($manager);
    }
    
    /**
     * Get the Options Manager for the current instance
     *
     * @return ManagerInterface The Options Manager for the current instance
     */
    public function getOptionsManager(): ManagerInterface
    {
        if (!isset($this->optionsManager)) {
            $this->initializeOptions();
        }

        if (!$this->optionsManager instanceof ManagerInterface) {
            throw new LogicException(sprintf(
                'Options helpers expected an initialized "%s"; got "%s".',
                ManagerInterface::class,
                get_debug_type($this->optionsManager)
            ));
        }

        return $this->optionsManager;
    }
    
    /**
     * Sets the options manager.
     *
     * @param ManagerInterface $optionsManager The options manager to be set.
     * 
     * @return void
     */
    public function setOptionsManager(ManagerInterface $optionsManager): void
    {
        $this->optionsManager = $optionsManager;
    }
}
