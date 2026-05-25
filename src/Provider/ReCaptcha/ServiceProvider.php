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

namespace PhalconKit\Provider\ReCaptcha;

use PhalconKit\Di\DiInterface;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the ReCaptcha verifier service.
 *
 * The provider reads `reCaptcha` config and applies optional hostname, Android
 * package, action, and score-threshold expectations to the verifier. Keeping
 * those expectations in config makes controller validation logic smaller and
 * keeps bot-protection policy centralized.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'reCaptcha';
    
    /**
     * Register the shared `reCaptcha` service.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            
            $config = $di->getConfig();
            
            $options = $config->pathToArray('reCaptcha', []);
            
            $secret = $options['secret'] ?? null;
            $requestMethod = $options['requestMethod'] ?? null;
            
            $reCaptcha = new \ReCaptcha\ReCaptcha($secret ?: '', $requestMethod);
            $reCaptcha->setExpectedHostname($options['expectedHostname'] ?? '');
            $reCaptcha->setExpectedApkPackageName($options['expectedApkPackageName'] ?? '');
            $reCaptcha->setExpectedAction($options['expectedAction'] ?? '');
            $reCaptcha->setScoreThreshold($options['scoreThreshold'] ?? 0.5);
            
            return $reCaptcha;
        });
    }
}
