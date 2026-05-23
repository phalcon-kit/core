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

namespace PhalconKit\Provider\Assets;

use PhalconKit\Di\DiInterface;
use Phalcon\Html\Escaper\EscaperInterface;
use PhalconKit\Html\TagFactory;
use PhalconKit\Assets\Manager;
use PhalconKit\Provider\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'assets';
    
    #[\Override]
    public function register(DiInterface $di): void
    {
        $escaper = $di->getTyped('escaper', EscaperInterface::class);
        
        // @todo set tag factory as tag service and use service instead
//        $tag = $di->get('tag');
        $tag = new TagFactory($escaper);
        
        $di->setShared($this->getName(), function () use ($tag) {
            return new Manager($tag);
        });
    }
}
