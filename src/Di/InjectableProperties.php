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

/**
 * @property \PhalconKit\Acl\AclInterface|\PhalconKit\Acl\Acl $acl
 * @property \PhalconKit\Assets\Manager $assets
 * @property \PhalconKit\Bootstrap $bootstrap
 * @property \PhalconKit\Bootstrap\Config $config
 * @property \PhalconKit\Cache\Cache $cache
 * @property \PhalconKit\Cli\Console $console
 * @property \PhalconKit\Support\Debug $debug
 * @property \PhalconKit\Dispatcher\AbstractDispatcher $dispatcher
 * @property \PhalconKit\Support\Env $env
 * @property \PhalconKit\Html\Escaper $escaper
 * @property \PhalconKit\Filter\Filter $filter
 * @property \PhalconKit\Support\HelperFactory $helper
 * @property \PhalconKit\Identity\Manager $identity
 * @property \PhalconKit\Locale $locale
 * @property \PhalconKit\Logger\Loggers $loggers
 * @property \PhalconKit\Support\Models $models
 * @property \PhalconKit\Mvc\Application $application
 * @property \PhalconKit\Mvc\Model\ManagerInterface $modelsManager
 * @property \PhalconKit\Db\Profiler $profiler
 * @property \PhalconKit\Http\RequestInterface $request
 * @property \PhalconKit\Http\ResponseInterface $response
 * @property \PhalconKit\Mvc\Router $router
 * @property \PhalconKit\Encryption\Security $security
 * @property \PhalconKit\Tag $tag
 * @property \PhalconKit\Support\Utils $utils
 * @property \PhalconKit\Support\Version $version
 * @property \PhalconKit\Mvc\Url $url
 * @property \PhalconKit\Mvc\View $view
 * @property \PhalconKit\Provider\Jwt\Jwt $jwt
 *
 * @property \Phalcon\Annotations\Adapter\AdapterInterface $annotations
 * @property \Phalcon\Http\Response\Cookies $cookies
 * @property \Phalcon\Encryption\Crypt $crypt
 * @property \Phalcon\Db\Adapter\AbstractAdapter $db
 * @property \Phalcon\Db\Adapter\AbstractAdapter $dbd
 * @property \Phalcon\Db\Adapter\AbstractAdapter $dbr
 * @property \Phalcon\Contracts\Events\Manager $eventsManager
 * @property \Phalcon\Flash\FlashInterface $flash
 * @property \Phalcon\Logger\LoggerInterface $logger
 * @property \Phalcon\Incubator\Mailer\Manager $mailer
 * @property \Phalcon\Cache\CacheInterface $modelsCache
 * @property \Phalcon\Mvc\Model\MetaDataInterface $modelsMetadata
 * @property \Phalcon\Session\ManagerInterface $session
 * @property \Phalcon\Translate\Adapter\AdapterInterface $translate
 * @property \Phalcon\Mvc\View\Engine\Volt $volt
 * @property \Phalcon\Di\FactoryDefault|\Phalcon\Di\Di|\Phalcon\Di\DiInterface $di
 *
 * @property \Aws\Sdk $aws
 * @property \League\Flysystem\Filesystem $fileSystem
 * @property \League\OAuth2\Client\Provider\GenericProvider $oauth2Client
 * @property \League\OAuth2\Client\Provider\Facebook $oauth2Facebook
 * @property \League\OAuth2\Client\Provider\Google $oauth2Google
 * @property \joshtronic\LoremIpsum $loremIpsum
 * @property \OpenAI\Contracts\ClientContract $openAi
 * @property \PhpImap\Mailbox $imap
 * @property \ReCaptcha\ReCaptcha $reCaptcha
 * @property \thiagoalessio\TesseractOCR\TesseractOCR $ocr
 * @property \Xenolope\Quahog\Client $clamav
 */
trait InjectableProperties
{
}
