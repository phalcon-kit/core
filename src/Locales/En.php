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

namespace PhalconKit\Locales;

use Phalcon\Translate\Adapter\NativeArray;
use Phalcon\Translate\InterpolatorFactory;

/**
 * Built-in English translation adapter for framework messages.
 *
 * The adapter ships PhalconKit's default `en_CA.UTF-8` strings and accepts
 * application-provided NativeArray options so callers can override or extend
 * the bundled content. It remains a normal Phalcon NativeArray adapter, so it
 * can be registered anywhere Phalcon expects a translate adapter.
 *
 * @see https://docs.phalcon.io/5.15/translate/
 */
class En extends NativeArray
{
    /**
     * Create the English locale adapter.
     *
     * The default domain is `phalcon-kit`. On Unix-like systems where
     * `LC_MESSAGES` exists, it is passed to Phalcon so locale category handling
     * matches native gettext conventions. User options are merged recursively so
     * custom content can be layered over the bundled framework strings.
     *
     * @param InterpolatorFactory $interpolator Factory used by Phalcon to
     *     interpolate placeholders.
     * @param array<string, mixed> $options NativeArray options, commonly
     *     `content`, `locale`, `defaultDomain`, or `category`.
     */
    public function __construct(InterpolatorFactory $interpolator, array $options = [])
    {
        $config = [
            'locale' => 'en_CA.UTF-8',
            'defaultDomain' => 'phalcon-kit',
            'content' => [
                'powered-by' => 'Powered by %phalcon-kit%.',
                'copyright' => '%phalcon-kit% &copy; 2017 Phalcon Kit.',
            ],
        ];
        
        if (defined('LC_MESSAGES')) {
            $config['category'] = LC_MESSAGES;
        }
        
        parent::__construct($interpolator, array_merge_recursive($config, $options));
    }
}
