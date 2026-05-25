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

namespace PhalconKit\Mvc\Controller\Traits\Actions\Rest;

use Phalcon\Filter\Exception as FilterException;
use Phalcon\Http\ResponseInterface;
use PhalconKit\Exception\LogicException;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractInjectable;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractRestResponse;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractSave;

/**
 * REST save / create / update actions.
 *
 * Responsibilities:
 * - Delegate persistence to the Save trait
 * - Translate save results into correct HTTP semantics
 *
 * Non-responsibilities:
 * - No persistence logic
 * - No validation logic
 * - No inference of entity-level errors
 */
trait SaveAction
{
    use AbstractInjectable;
    use AbstractRestResponse;
    use AbstractSave;

    /* ==========================================================
     * Public REST actions
     * ======================================================== */

    /**
     * Generic save endpoint.
     *
     * - Auto-detects creation vs. update
     * - Supports single or batch payloads
     * - Backward compatible entry point
     *
     * @throws FilterException When request payload filtering fails.
     * @throws LogicException When persistence intent resolution returns an
     *     inconsistent framework state.
     */
    public function saveAction(): ResponseInterface
    {
        return $this->respondFromSaveResult(
            $this->save()
        );
    }

    /**
     * Explicitly create endpoint.
     *
     * - Forces creation semantics
     * - Single entity success → 201 Created
     * - Batch semantics unchanged (200 / 207 / 422)
     *
     * @throws FilterException When request payload filtering fails.
     * @throws LogicException When persistence intent resolution returns an
     *     inconsistent framework state.
     */
    public function createAction(): ResponseInterface
    {
        $ret = $this->create();

        // REST purity: single successful create returns 201
        if (!isset($ret[self::REST_VIEW_RESULTS]) && $ret[self::REST_VIEW_SAVED] === true) {
            $this->response->setStatusCode(201, 'Created');
        }

        return $this->respondFromSaveResult($ret);
    }

    /**
     * Explicit update endpoint.
     *
     * - Forces update semantics
     * - Success → 200 OK
     *
     * @throws FilterException When request payload filtering fails.
     * @throws LogicException When persistence intent resolution returns an
     *     inconsistent framework state.
     */
    public function updateAction(): ResponseInterface
    {
        $ret = $this->update();

        // Explicit update success stays 200
        if (!isset($ret[self::REST_VIEW_RESULTS]) && $ret[self::REST_VIEW_SAVED] === true) {
            $this->response->setStatusCode(200, 'OK');
        }

        return $this->respondFromSaveResult($ret);
    }

    /* ==========================================================
     * Response normalization (single source of truth)
     * ======================================================== */

    /**
     * Normalizes a save() result into a REST response.
     *
     * Rules enforced here:
     *
     * Single entity:
     * - 200 OK → success
     * - 400 Bad Req → malformed / invalid input
     * - 422 Unprocessable → validation / domain failure
     *
     * Batch:
     * - 200 OK → all entities saved
     * - 207 Multi → mixed success / failure
     * - 422 Unprocessable → all entities failed
     *
     * @param array $ret Result returned by save(), create(), or update()
     */
    protected function respondFromSaveResult(array $ret): ResponseInterface
    {
        /* ---------- Batch handling ---------- */

        if (isset($ret[self::REST_VIEW_RESULTS])) {
            $hasSuccess = false;
            $hasFailure = false;

            foreach ($ret[self::REST_VIEW_RESULTS] as $row) {
                if (($row[self::REST_VIEW_SAVED] ?? false) === true) {
                    $hasSuccess = true;
                } else {
                    $hasFailure = true;
                }
            }

            if ($hasSuccess && $hasFailure) {
                // Partial success is NOT an error
                $this->response->setStatusCode(207, 'Multi-Status');
            } elseif ($hasFailure) {
                // All entities failed validation / save
                $this->response->setStatusCode(422, 'Unprocessable Entity');
            } else {
                // All entities saved
                $this->response->setStatusCode(200, 'OK');
            }

            $this->setRestViewVars($ret);

            // REST success only if no failures
            return $this->setRestResponse($hasFailure === false);
        }

        /* ---------- Single entity ---------- */

        if ($ret[self::REST_VIEW_SAVED] !== true) {
            // Distinguish malformed vs domain failure
            $this->response->setStatusCode(
                empty($ret[self::REST_VIEW_MESSAGES] ?? null)
                    ? 422 // domain / validation failure
                    : 400 // malformed / invalid request
            );
        }

        $this->setRestViewVars($ret);

        return $this->setRestResponse($ret[self::REST_VIEW_SAVED] === true);
    }
}
