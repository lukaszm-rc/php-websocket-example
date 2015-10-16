<?php
namespace WebSocketClient\Tests\WebSocketClient;

use PHPUnit_Framework_TestCase;
use Ratchet\ConnectionInterface;
use React\EventLoop\Factory;
use React\EventLoop\StreamSelectLoop;
use WebSocketClient\Tests\Client;
use WebSocketClient\Tests\Server;

class MessageTest extends PHPUnit_Framework_TestCase
{
    private $host = '127.0.0.1';
    private $port;
    private $path = '/mytest';

    /**
     * @var StreamSelectLoop
     */
    private $loop;

    /**
     * @var Server
     */
    private $server;

    public function setUp()
    {
        $this->port = !empty($GLOBALS['port']) ? (int)$GLOBALS['port'] : 8080;
        $this->loop = Factory::create();
        $this->server = new Server($this->loop, $this->port, $this->path);

        $loop = $this->loop;
        $this->loop->addPeriodicTimer(10, function () use ($loop) {
            $loop->stop();
        });
    }

    public function tearDown()
    {
        $this->server->close();
    }

    public function testMessage()
    {
        $loop = $this->loop;

        $subscribed = null;
        $this->server->setOnMessageCallback(function (ConnectionInterface $conn, $topic) use (&$subscribed, $loop) {
            /** @var \Ratchet\Wamp\Topic $topic */
            $subscribed = $topic->getId();
            $loop->stop();
        });

        $client = new Client($loop, $this->host, $this->port, $this->path);

        $client->setOnMessageCallback(function (Client $conn, $data) use (&$response, $loop) {
            $conn->send('this_is_my_topic');
        });

        $loop->run();

        $this->assertEquals('this_is_my_topic', $subscribed);
    }

}
