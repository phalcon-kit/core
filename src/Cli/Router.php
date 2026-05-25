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
 * It intentionally does not declare `Phalcon\Cli\RouterInterface`: in Phalcon
 * 5.13 the native `Phalcon\Cli\Router` method signatures are incompatible with
 * that interface, so a subclass cannot implement both without a PHP fatal
 * error. Tests guard this constraint so the class can be updated if upstream
 * Phalcon aligns the signatures later.
 *
 * @see https://docs.phalcon.io/5.13/application-cli/
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
