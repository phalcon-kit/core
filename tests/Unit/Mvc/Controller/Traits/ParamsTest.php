<?php

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Controller\Traits;

use PhalconKit\Http\Request;
use PhalconKit\Mvc\Controller;
use PhalconKit\Mvc\Controller\Traits\Params;
use PhalconKit\Tests\Unit\AbstractUnit;

class ParamsTest extends AbstractUnit
{
    public function testPostParamsUsePostBodyWithoutConcatenatingPatchBody(): void
    {
        $payload = [
            [
                'recordId' => 87443,
                'content' => 'test',
            ],
        ];

        $controller = $this->createController(
            $this->createRequest(
                isPost: true,
                post: $payload,
                patch: $payload,
            )
        );

        $this->assertSame($payload, $controller->getParams());
    }

    public function testPatchParamsUsePatchBodyWithoutMergingPostBody(): void
    {
        $postPayload = [
            [
                'source' => 'post',
            ],
        ];
        $patchPayload = [
            [
                'source' => 'patch',
            ],
        ];

        $controller = $this->createController(
            $this->createRequest(
                isPatch: true,
                post: $postPayload,
                patch: $patchPayload,
            )
        );

        $this->assertSame($patchPayload, $controller->getParams());
    }

    private function createController(Request $request): object
    {
        $this->di?->set('request', $request);

        $controller = new class extends Controller {
            use Params;
        };
        $controller->setDI($this->di);

        return $controller;
    }

    /**
     * @param array<array-key, mixed> $post
     * @param array<array-key, mixed> $patch
     */
    private function createRequest(
        bool $isPost = false,
        bool $isPatch = false,
        bool $isPut = false,
        array $post = [],
        array $patch = [],
        array $put = [],
        array $query = [],
    ): Request {
        return new class ($isPost, $isPatch, $isPut, $post, $patch, $put, $query) extends Request {
            public function __construct(
                private readonly bool $postRequest,
                private readonly bool $patchRequest,
                private readonly bool $putRequest,
                private readonly array $postPayload,
                private readonly array $patchPayload,
                private readonly array $putPayload,
                private readonly array $queryPayload,
            ) {
            }

            #[\Override]
            public function isPost(): bool
            {
                return $this->postRequest;
            }

            #[\Override]
            public function isPatch(): bool
            {
                return $this->patchRequest;
            }

            #[\Override]
            public function isPut(): bool
            {
                return $this->putRequest;
            }

            #[\Override]
            public function getPost(
                ?string $name = null,
                $filters = null,
                $defaultValue = null,
                bool $notAllowEmpty = false,
                bool $noRecursive = false
            ) {
                return $this->postPayload;
            }

            #[\Override]
            public function getPatch(
                ?string $name = null,
                $filters = null,
                $defaultValue = null,
                bool $notAllowEmpty = false,
                bool $noRecursive = false
            ) {
                return $this->patchPayload;
            }

            #[\Override]
            public function getPut(
                ?string $name = null,
                $filters = null,
                $defaultValue = null,
                bool $notAllowEmpty = false,
                bool $noRecursive = false
            ) {
                return $this->putPayload;
            }

            #[\Override]
            public function getQuery(
                ?string $name = null,
                $filters = null,
                $defaultValue = null,
                bool $notAllowEmpty = false,
                bool $noRecursive = false
            ) {
                return $this->queryPayload;
            }
        };
    }
}
