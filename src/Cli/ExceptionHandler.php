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

use PhalconKit\Exception\CliException;

/**
 * Minimal CLI exception/message writer.
 *
 * The handler is intentionally small: it writes a string, Exception, or any
 * Throwable to a stream and appends one trailing newline. A `CliException` is
 * an expected, user-facing command failure (an unknown or denied task, an
 * unknown output format), so only its message is written; any other Throwable
 * is written in full, with its file, line, and stack trace. It is useful during
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
            match (true) {
                is_string($this->e) => $this->e,
                $this->e instanceof CliException => $this->e->getMessage(),
                default => (string) $this->e,
            } . PHP_EOL
        );
    }
}
