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

namespace PhalconKit\Mvc\Controller\Traits\Interfaces;

/**
 * Contract for attaching controller behavior listeners.
 *
 * Behaviors are regular event listeners attached to the controller's events
 * manager. REST controllers use them to apply role, feature, and model-specific
 * behavior during request initialization.
 */
interface BehaviorInterface
{
    /**
     * Attach one behavior listener class.
     *
     * @param class-string $eventClass Listener class to instantiate or resolve.
     * @param string|null $eventType Event type, usually `rest` or `model`.
     * @param int|null $priority Optional event-manager priority.
     */
    public function attachBehavior(string $eventClass, ?string $eventType = null, ?int $priority = null): void;
    
    /**
     * Attach multiple behavior listener definitions.
     *
     * @param array<int|string, mixed> $behaviors Behavior class names or nested
     *     listener definitions.
     * @param string|null $eventType Default event type for class-name entries.
     * @param int|null $priority Optional event-manager priority.
     */
    public function attachBehaviors(array $behaviors = [], ?string $eventType = null, ?int $priority = null): void;
}
