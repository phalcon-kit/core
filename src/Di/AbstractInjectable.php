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

namespace PhalconKit\Di;

use Phalcon\Di\DiInterface;

/**
 * Trait AbstractInjectable
 *
 * This trait provides a common trait for traits that are injectable
 * and depend on a dependency injection container.
 *
 * @property \PhalconKit\Cli\Dispatcher|\PhalconKit\Mvc\Dispatcher|\PhalconKit\Dispatcher\DispatcherInterface|\Phalcon\Mvc\Dispatcher|\Phalcon\Mvc\DispatcherInterface $dispatcher
 * @property \PhalconKit\Cli\Router|\PhalconKit\Mvc\Router|\PhalconKit\Router\RouterInterface|\Phalcon\Mvc\Router|\Phalcon\Mvc\RouterInterface $router
 * @property \PhalconKit\Mvc\Url|\Phalcon\Mvc\Url|\Phalcon\Mvc\Url\UrlInterface $url
 * @property \PhalconKit\Http\Request|\PhalconKit\Http\RequestInterface|\Phalcon\Http\Request|\Phalcon\Http\RequestInterface $request
 * @property \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface $response
 * @property \Phalcon\Http\Response\Cookies|\Phalcon\Http\Response\CookiesInterface $cookies
 * @property \PhalconKit\Filter\Filter|\Phalcon\Filter\Filter $filter
 * @property \Phalcon\Flash\Direct $flash
 * @property \Phalcon\Flash\Session $flashSession
 * @property \Phalcon\Session\ManagerInterface $session
 * @property \Phalcon\Events\Manager|\Phalcon\Contracts\Events\Manager $eventsManager
 * @property \Phalcon\Db\Adapter\AdapterInterface $db
 * @property \PhalconKit\Encryption\Security|\Phalcon\Encryption\Security $security
 * @property \Phalcon\Encryption\Crypt|\Phalcon\Encryption\Crypt\CryptInterface $crypt
 * @property \PhalconKit\Tag|\Phalcon\Tag $tag
 * @property \PhalconKit\Html\Escaper|\PhalconKit\Html\Escaper\EscaperInterface|\Phalcon\Html\Escaper|\Phalcon\Html\Escaper\EscaperInterface $escaper
 * @property \Phalcon\Annotations\Adapter\Memory|\Phalcon\Annotations\Adapter $annotations
 * @property \Phalcon\Mvc\Model\Manager|\Phalcon\Mvc\Model\ManagerInterface $modelsManager
 * @property \Phalcon\Mvc\Model\MetaData\Memory|\Phalcon\Mvc\Model\MetadataInterface $modelsMetadata
 * @property \Phalcon\Mvc\Model\Transaction\Manager|\Phalcon\Mvc\Model\Transaction\ManagerInterface $transactionManager
 * @property \Phalcon\Assets\Manager $assets
 * @property \Phalcon\Di\Di|\Phalcon\Di\DiInterface $di
 * @property \Phalcon\Session\Bag|\Phalcon\Session\BagInterface $persistent
 * @property \Phalcon\Mvc\View|\Phalcon\Mvc\ViewInterface $view
 *
 * @property \PhalconKit\Bootstrap\Config $config
 * @property \PhalconKit\Bootstrap $bootstrap
 * @property \PhalconKit\Support\Debug $debug
 * @property \PhalconKit\Identity\Manager $identity
 * @property \PhalconKit\Locale $locale
 * @property \PhalconKit\Support\Utils $utils
 * @property \PhalconKit\Db\Profiler $profiler
 * @property \Phalcon\Logger\Logger $logger
 * @property \PhalconKit\Provider\Jwt\Jwt $jwt
 * @property \PhalconKit\Support\Models $models
 *
 * @property \OpenAI\Contracts\ClientContract $openAi
 * @property joshtronic\LoremIpsum $loremIpsum
 */
trait AbstractInjectable
{
    abstract public function setDI(DiInterface $di): void;
    
    abstract public function getDI(): DiInterface;
}
