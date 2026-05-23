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

namespace PhalconKit\Provider;

use LogicException;
use PhalconKit\Di\DiInterface;
use PhalconKit\Di\Injectable;

/**
 * Class AbstractServiceProvider
 */
abstract class AbstractServiceProvider extends Injectable implements ServiceProviderInterface
{
    /**
     * The Service name.
     */
    protected string $serviceName;
    
    /**
     * Set DI and run configure();
     */
    public function __construct(DiInterface $di)
    {
        if (empty($this->serviceName)) {
            throw new LogicException(sprintf('The service provider defined in "%s" cannot have an empty name.', get_class($this)));
        }
        $this->setDI($di);
        $this->configure();
    }
    
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getName(): string
    {
        return $this->serviceName;
    }
    
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function boot(): void
    {
    }
    
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function configure(): void
    {
    }
}
