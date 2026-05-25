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

/**
 * Minimal CLI exception/message writer.
 *
 * The handler is intentionally small: it writes a string, Exception, or any
 * Throwable to a stream and appends one trailing newline. It is useful during
 * early CLI bootstrap where a full logger service may not be available yet.
 */
class ExceptionHandler
{
    /**
     * @param string|\Exception|\Throwable $e Message or throwable to render.
     * @param mixed $outputStream Writable stream resource, defaulting to
     *     STDERR.
     */
    public function __construct(
        private string|\Exception|\Throwable $e,
        private readonly mixed $outputStream = STDERR,
    ) {}
    
    /**
     * Write the configured message/throwable to the output stream.
     *
     * @return void
     */
    public function write(): void
    {
        fwrite(
            $this->outputStream,
            (is_string($this->e) ? $this->e : (string) $this->e) . PHP_EOL
        );
    }
}
