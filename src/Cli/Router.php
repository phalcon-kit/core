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

namespace PhalconKit\Cli;

use PhalconKit\Router\RouterInterface;

/**
 * CLI router with PhalconKit diagnostic state export.
 *
 * The class must extend Phalcon's native CLI router because Phalcon CLI
 * applications and modules expect that concrete runtime behavior. It also
 * implements PhalconKit's shared router interface for typed DI lookups.
 *
 * Phalcon 5.14 aligns the native CLI router with `Phalcon\Cli\RouterInterface`,
 * so this wrapper now satisfies both the native router interface inherited from
 * the parent and PhalconKit's shared router interface.
 *
 * @see https://docs.phalcon.io/5.14/application-cli/
 */
class Router extends \Phalcon\Cli\Router implements RouterInterface
{
    /**
     * Export the current CLI router match state for diagnostics.
     *
     * @return array<string, mixed> Current module, task, action, params,
     *     matches, and matched-route metadata.
     */
    #[\Override]
    public function toArray(): array
    {
        $matchedRoute = $this->getMatchedRoute();
        return [
            'module' => $this->getModuleName(),
            'task' => $this->getTaskName(),
            'action' => $this->getActionName(),
            'params' => $this->getParams(),
            'matches' => $this->getMatches(),
            'matched' => $matchedRoute ? [
                'id' => $matchedRoute->getRouteId(),
                'name' => $matchedRoute->getName(),
                'paths' => $matchedRoute->getPaths(),
                'pattern' => $matchedRoute->getPattern(),
                'reversedPaths' => $matchedRoute->getReversedPaths(),
            ] : null,
        ];
    }
}
