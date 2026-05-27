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
     * - 400 Bad Req → malformed input without validation/domain messages
     * - 422 Unprocessable → validation/domain failure with messages
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

            $this->setRestViewVars($ret);

            if ($hasSuccess && $hasFailure) {
                // Partial success is not an error, but the envelope response is
                // false so clients can detect that not every row persisted.
                return $this->setRestResponse(false, 207, 'Multi-Status');
            }

            if ($hasFailure) {
                // All entities failed validation or persistence.
                return $this->setRestErrorResponse(422, response: false);
            }

            return $this->setRestResponse(true, 200);
        }

        /* ---------- Single entity ---------- */

        $this->setRestViewVars($ret);

        if ($ret[self::REST_VIEW_SAVED] !== true) {
            return $this->setRestErrorResponse($this->getSaveActionFailureStatusCode($ret), response: false);
        }

        return $this->setRestResponse(true);
    }

    /**
     * Resolve the HTTP status code for a failed single-entity save.
     *
     * Model, validation, and domain-rule failures normally include messages and
     * map to 422 Unprocessable Entity. Framework-generated REST failures use
     * Phalcon message codes for request problems such as invalid create/update
     * intent or not-found identities; those explicit 4xx/5xx codes are
     * preserved so the action layer does not collapse them into validation
     * responses.
     *
     * A failure without messages is treated as a malformed request because the
     * persistence layer could not provide a domain-specific reason for the
     * rejection.
     *
     * @param array $ret Single-entity save result.
     */
    protected function getSaveActionFailureStatusCode(array $ret): int
    {
        return $this->getRestActionFailureStatusCode($ret[self::REST_VIEW_MESSAGES] ?? null);
    }
}
