<?php
require 'vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Server\IoServer;
use React\EventLoop\Loop;
use React\Socket\SocketServer;

class TrackingRelay implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {}

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }

    public function broadcast($data) {
        foreach ($this->clients as $client) {
            $client->send($data);
        }
    }
}

$loop = Loop::get();
$relay = new TrackingRelay();

$wsSocket = new SocketServer('0.0.0.0:8081', [], $loop);
new IoServer(new HttpServer(new WsServer($relay)), $wsSocket, $loop);

$internalSocket = new SocketServer('0.0.0.0:8082', [], $loop);
$internalSocket->on('connection', function ($conn) use ($relay) {
    $conn->on('data', function ($data) use ($relay) {
        $relay->broadcast(trim($data));
    });
});

$loop->run();
