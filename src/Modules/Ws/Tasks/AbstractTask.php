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
    
    public function listenAction(): void
    {
        $this->server->start();
    }
    
    // --- Initialization methods ---
    
    public function initializeOpen(): void
    {
        $this->onOpen = function (Server $server, Request $request): void {
            $this->resetConnectionState();
            $this->onOpen($server, $request);
        };
    }
    
    public function initializeMessage(): void
    {
        $this->onMessage = function (Server $server, Frame $frame): void {
            $this->resetConnectionState();
            $this->onMessage($server, $frame);
        };
    }
    
    public function initializeClose(): void
    {
        $this->onClose = function (Server $server, int $fd): void {
            $this->resetConnectionState();
            $this->onClose($server, $fd);
        };
    }
    
    public function initializeWorkerError(): void
    {
        $this->onWorkerError = fn(Server $server, int $fd, int $code, string $reason): null => $this->onWorkerError($server, $fd, $code, $reason);
    }
    
    public function initializeStart(): void
    {
        $this->onStart = fn(Server $server): null => $this->onStart($server);
    }
    
    public function initializeWorkerStart(): void
    {
        $this->onWorkerStart = fn(Server $server, int $workerId): null => $this->onWorkerStart($server, $workerId);
    }
    
    public function initializeShutdown(): void
    {
        $this->onShutdown = fn(Server $server): null => $this->onShutdown($server);
    }
    
    public function initializeRequest(): void
    {
        $this->onRequest = function (Request $request, Response $response): void {
            $this->resetConnectionState();
            $this->onRequest($request, $response);
        };
    }
    
    public function initializePipeMessage(): void
    {
        $this->onPipeMessage = function (Server $server, int $srcWorkerId, mixed $data): void {
            $this->resetConnectionState();
            $this->onPipeMessage($server, $srcWorkerId, $data);
        };
    }
    
    // --- Event handlers to override in child classes ---
    
    public function onOpen(Server $server, Request $request): void
    {
        $this->log("Client connected: fd={$request->fd}");
    }
    
    public function onMessage(Server $server, Frame $frame): void
    {
        $this->log("Received message from fd={$frame->fd} data={$frame->data}");
    }
    
    public function onClose(Server $server, int $fd): void
    {
        $this->log("Client fd={$fd} disconnected");
    }
    
    public function onWorkerError(Server $server, int $fd, int $code, string $reason): void
    {
        $this->log("Worker error: fd={$fd}, code={$code}, reason={$reason}");
    }
    
    public function onStart(Server $server): void
    {
        $this->log("WebSocket server started on {$server->host}:{$server->port}");
    }
    
    public function onWorkerStart(Server $server, int $workerId): void
    {
        $this->log("Worker #{$workerId} started");
    }
    
    public function onShutdown(Server $server): void
    {
        $this->log('Server shutting down');
    }
    
    public function onRequest(Request $request, Response $response): void
    {
        $this->log("HTTP request received from {$request->server['remote_addr']}: {$request->server['request_uri']}");
        $response->end('Default HTTP handler');
    }
    
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
