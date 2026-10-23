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

namespace PhalconKit\OpenAi;

use Closure;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;
use Throwable;

/**
 * Observe bytes already read by the caller; never prefetch or cache a whole SSE stream.
 *
 * @internal Used by UsageCapture; transient decode buffers are discarded on completion.
 */
final class UsageCaptureStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private const int MAX_BUFFER_BYTES = 8 * 1024 * 1024;

    private string $buffer = '';
    private bool $disabled = false;
    private bool $discardLine = false;
    private bool $started = false;

    /** @param Closure(string): void $observer Receives complete JSON payloads only. */
    public function __construct(
        private StreamInterface $stream,
        private readonly Closure $observer,
        private readonly bool $eventStream
    ) {
    }

    #[\Override]
    public function __toString(): string
    {
        if ($this->stream->isSeekable()) {
            // The underlying __toString() returns the complete body after rewinding.
            $this->buffer = '';
        }
        try {
            $contents = (string) $this->stream;
        } catch (Throwable $exception) {
            $this->disable();
            throw $exception;
        }
        $this->observe($contents, true);
        return $contents;
    }

    #[\Override]
    public function read(int $length): string
    {
        try {
            $contents = $this->stream->read($length);
        } catch (Throwable $exception) {
            $this->disable();
            throw $exception;
        }
        $this->observe($contents, $this->stream->eof());
        return $contents;
    }

    #[\Override]
    public function getContents(): string
    {
        try {
            $contents = $this->stream->getContents();
        } catch (Throwable $exception) {
            $this->disable();
            throw $exception;
        }
        $this->observe($contents, $this->stream->eof());
        return $contents;
    }

    #[\Override]
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->stream->seek($offset, $whence);
        // A successful reposition can skip or repeat bytes; stop observing that body.
        if ($this->started || $offset !== 0 || $whence !== SEEK_SET) {
            $this->disable();
        }
    }

    #[\Override]
    public function close(): void
    {
        $this->disable();
        $this->stream->close();
    }

    #[\Override]
    public function detach()
    {
        $this->disable();
        return $this->stream->detach();
    }

    private function observe(string $contents, bool $eof): void
    {
        if ($this->disabled) {
            return;
        }
        $this->started = $this->started || $contents !== '';
        if (!$this->eventStream) {
            if (strlen($this->buffer) + strlen($contents) > self::MAX_BUFFER_BYTES) {
                $this->disable();
                return;
            }
            $this->buffer .= $contents;
            if ($eof) {
                ($this->observer)($this->buffer);
                $this->disable();
            }
            return;
        }

        $offset = 0;
        while (($end = strpos($contents, "\n", $offset)) !== false) {
            $this->appendLine(substr($contents, $offset, $end - $offset));
            $this->finishLine();
            $offset = $end + 1;
        }
        $this->appendLine(substr($contents, $offset));
        if ($eof) {
            $this->finishLine();
            $this->disable();
        }
    }

    private function appendLine(string $part): void
    {
        if ($this->discardLine) {
            return;
        }
        if (strlen($this->buffer) + strlen($part) > self::MAX_BUFFER_BYTES) {
            $this->buffer = '';
            $this->discardLine = true;
            return;
        }
        $this->buffer .= $part;
    }

    private function finishLine(): void
    {
        if (!$this->discardLine && str_starts_with($this->buffer, 'data:')) {
            ($this->observer)(substr($this->buffer, 5));
        }
        $this->buffer = '';
        $this->discardLine = false;
    }

    private function disable(): void
    {
        $this->disabled = true;
        $this->buffer = '';
    }
}
