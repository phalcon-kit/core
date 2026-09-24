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

namespace PhalconKit\Modules\Ws\Tasks;

use Swoole\WebSocket\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use PhalconKit\Modules\Ws\Task;

/**
 * WebSocket task with overridable Swoole event hooks.
 *
 * Requires the shared `swoole` DI service to provide a WebSocket server using
 * positional event arguments (`event_object` disabled). Request/message callbacks
 * reset model connection state before invoking application hooks. Subscriptions
 * belong to the current worker process; applications coordinate other workers.
 */
abstract class AbstractTask extends Task
{
    public static array $subscriptions = [];
    
    public \Closure $onOpen;
    public \Closure $onClose;
    public \Closure $onMessage;
    public \Closure $onWorkerError;
    public \Closure $onStart;
    public \Closure $onWorkerStart;
    public \Closure $onShutdown;
    public \Closure $onRequest;
    public \Closure $onPipeMessage;
    
    public Server $server;
    
    /**
     * Resolve the shared `swoole` service, build callbacks, and register them.
     * Call the parent when overriding initialization to retain event dispatch.
     */
    public function initialize(): void
    {
        $this->initializeOpen();
        $this->initializeMessage();
        $this->initializeClose();
        $this->initializeWorkerError();
        $this->initializeStart();
        $this->initializeWorkerStart();
        $this->initializeShutdown();
        $this->initializeRequest();
        $this->initializePipeMessage();
        
        $this->server = $this->di->getShared('swoole');
        $this->handleWebSocket();
    }
    
    /**
     * Register the initialized callbacks on the server without starting its event loop.
     */
    public function handleWebSocket(): void
    {
        $this->server->on('start', $this->onStart);
        $this->server->on('workerStart', $this->onWorkerStart);
        $this->server->on('shutdown', $this->onShutdown);
        $this->server->on('open', $this->onOpen);
        $this->server->on('message', $this->onMessage);
        $this->server->on('close', $this->onClose);
        $this->server->on('workerError', $this->onWorkerError);
        $this->server->on('request', $this->onRequest);
        $this->server->on('pipeMessage', $this->onPipeMessage);
    }
    
    /**
     * Start the configured server event loop; returns after the server stops.
     */
    public function listenAction(): void
    {
        $this->server->start();
    }
    
    // --- Initialization methods ---
    
    /**
     * Install the open callback, resetting model connection state before the hook.
     */
    public function initializeOpen(): void
    {
        $this->onOpen = function (Server $server, Request $request): void {
            $this->resetConnectionState();
            $this->onOpen($server, $request);
        };
    }
    
    /**
     * Install the message callback, resetting model connection state before the hook.
     */
    public function initializeMessage(): void
    {
        $this->onMessage = function (Server $server, Frame $frame): void {
            $this->resetConnectionState();
            $this->onMessage($server, $frame);
        };
    }
    
    /**
     * Install the close callback, resetting model connection state before the hook.
     */
    public function initializeClose(): void
    {
        $this->onClose = function (Server $server, int $fd): void {
            $this->resetConnectionState();
            $this->onClose($server, $fd);
        };
    }
    
    /**
     * Adapt Swoole's five worker-error arguments to the existing four-argument hook.
     *
     * Keep onWorkerError() overrides compatible while passing the actual exit code
     * and retaining both the worker PID and termination signal in the reason text.
     */
    public function initializeWorkerError(): void
    {
        $this->onWorkerError = function (Server $server, int $workerId, int $workerPid, int $exitCode, int $signal): void {
            $this->onWorkerError($server, $workerId, $exitCode, "pid={$workerPid}, signal={$signal}");
        };
    }
    
    /**
     * Install the master-process startup callback.
     */
    public function initializeStart(): void
    {
        $this->onStart = fn(Server $server): null => $this->onStart($server);
    }
    
    /**
     * Install the worker startup callback with its worker ID.
     */
    public function initializeWorkerStart(): void
    {
        $this->onWorkerStart = fn(Server $server, int $workerId): null => $this->onWorkerStart($server, $workerId);
    }
    
    /**
     * Install the server shutdown callback.
     */
    public function initializeShutdown(): void
    {
        $this->onShutdown = fn(Server $server): null => $this->onShutdown($server);
    }
    
    /**
     * Install the HTTP callback, resetting model connection state before the hook.
     */
    public function initializeRequest(): void
    {
        $this->onRequest = function (Request $request, Response $response): void {
            $this->resetConnectionState();
            $this->onRequest($request, $response);
        };
    }
    
    /**
     * Install the inter-worker message callback, resetting model connection state first.
     */
    public function initializePipeMessage(): void
    {
        $this->onPipeMessage = function (Server $server, int $srcWorkerId, mixed $data): void {
            $this->resetConnectionState();
            $this->onPipeMessage($server, $srcWorkerId, $data);
        };
    }
    
    // --- Event handlers to override in child classes ---
    
    /**
     * Handle a completed WebSocket handshake; the default logs the client descriptor.
     */
    public function onOpen(Server $server, Request $request): void
    {
        $this->log("Client connected: fd={$request->fd}");
    }
    
    /**
     * Handle a received WebSocket frame; the default logs its descriptor and payload.
     */
    public function onMessage(Server $server, Frame $frame): void
    {
        $this->log("Received message from fd={$frame->fd} data={$frame->data}");
    }
    
    /**
     * Handle a closed client descriptor; override to clean application subscription state.
     */
    public function onClose(Server $server, int $fd): void
    {
        $this->log("Client fd={$fd} disconnected");
    }
    
    /**
     * Handle a failed worker; override to integrate application monitoring.
     *
     * @param Server $server Server whose worker failed.
     * @param int $fd Worker ID, despite the historical parameter name; not a client descriptor.
     * @param int $code Worker exit code.
     * @param string $reason Worker process details, formatted as "pid=<pid>, signal=<signal>".
     */
    public function onWorkerError(Server $server, int $fd, int $code, string $reason): void
    {
        $this->log("Worker error: workerId={$fd}, exitCode={$code}, {$reason}");
    }
    
    /**
     * Handle master-process startup; the default logs the listening address.
     */
    public function onStart(Server $server): void
    {
        $this->log("WebSocket server started on {$server->host}:{$server->port}");
    }
    
    /**
     * Handle worker startup; override for resources owned by this worker process.
     */
    public function onWorkerStart(Server $server, int $workerId): void
    {
        $this->log("Worker #{$workerId} started");
    }
    
    /**
     * Handle server shutdown; the default logs completion.
     */
    public function onShutdown(Server $server): void
    {
        $this->log('Server shutting down');
    }
    
    /**
     * Handle an HTTP request on the WebSocket server.
     * The default logs the path and ends the response with a placeholder body.
     */
    public function onRequest(Request $request, Response $response): void
    {
        $this->log("HTTP request received from {$request->server['remote_addr']}: {$request->server['request_uri']}");
        $response->end('Default HTTP handler');
    }
    
    /**
     * Handle data sent by another worker.
     * The default logs string-compatible data; override for structured messages.
     */
    public function onPipeMessage(Server $server, int $srcWorkerId, mixed $data): void
    {
        $this->log("Pipe message received from worker #{$srcWorkerId}: data={$data}");
    }
    
    // --- Helper methods ---
    
    /**
     * Subscribes a client, identified by its file descriptor, to a specific channel.
     *
     * @param int $fd The file descriptor identifying the client.
     * @param string $channel The name of the channel to subscribe the client to.
     * @return void
     */
    public function subscribeClientToChannel(int $fd, string $channel): void
    {
        self::$subscriptions[$channel] ??= [];
        self::$subscriptions[$channel][$fd] = true;
        $this->log("Subscribed client fd={$fd} channel={$channel}");
    }
    
    /**
     * Unsubscribes a client, identified by its file descriptor, from a specific channel.
     *
     * @param int $fd The file descriptor identifying the client.
     * @param string $channel The name of the channel to unsubscribe the client from.
     * @return void
     */
    public function unsubscribeClientFromChannel(int $fd, string $channel): void
    {
        if (isset(self::$subscriptions[$channel][$fd])) {
            unset(self::$subscriptions[$channel][$fd]);
            $this->log("Unsubscribed client fd={$fd} channel={$channel}");
        }
    }
    
    /**
     * Broadcasts a message to all active subscribers of a specified channel. Optionally, the broadcast
     * can target a specific list of file descriptors.
     *
     * @param Server $server The server instance used to handle broadcasting and validating connections.
     * @param string $channel The channel name to which the message should be broadcasted.
     * @param array $data The message payload to be sent to the subscribers.
     * @param array|null $fdList Optional list of file descriptors to restrict the broadcast to specific clients.
     * @return void
     */
    public function broadcastToChannel(Server $server, string $channel, array $data, ?array $fdList = null): void
    {
        if (!isset(self::$subscriptions[$channel])) {
            $this->log("No subscribers for channel={$channel}");
            return;
        }
        
        $jsonData = json_encode($data);
        if (empty($jsonData)) {
            return;
        }
        
        $this->log("Broadcasting to channel={$channel} data=" . $jsonData);
        
        foreach (self::$subscriptions[$channel] as $fd => $active) {
            if (isset($fdList) && !in_array($fd, $fdList, true)) {
                continue;
            }
            if ($server->isEstablished($fd)) {
                $server->push($fd, $jsonData);
            }
        }
    }
    
    /**
     * Unsubscribes a client, identified by its file descriptor, from all subscribed channels.
     *
     * @param int $fd The file descriptor identifying the client.
     * @return void
     */
    public function unsubscribeClient(int $fd): void
    {
        foreach (self::$subscriptions as &$fds) {
            unset($fds[$fd]);
        }
    }
    
    /**
     * Logs a message with the worker ID of the specified server instance or the default server instance.
     *
     * @param string $message The message to log.
     * @param Server|null $server The server instance to use for retrieving the worker ID. If null, the default server instance will be used.
     * @return void
     */
    public function log(string $message, ?Server $server = null): void
    {
        $server ??= $this->server;
        $workerId = $server->getWorkerId();
        $worker = $workerId === false ? '[Master]' : "[Worker #{$workerId}]";
        echo "$worker $message\n";
    }
}
