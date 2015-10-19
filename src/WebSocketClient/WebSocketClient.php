<?php

namespace WebSocketClient;

use Closure;
use WebSocket\Client\WebSocketClientInterface;
use WebSocket\Client\WebSocketConnection;
use React\EventLoop\StreamSelectLoop;

define('DEV_MODE', true);
define("LOOP_TIME", 1);

/**
 * Implementacja zdarzen klienta.
 *
 * @author Lukasz Mazurek <lukasz.mazurek@redcart.pl>
 */
class WebSocketClient implements WebSocketClientInterface {

    public $client;
    public static $iResponses = 0;
    public static $iRequests = 0;
    public static $paused = false;

    /** @var callable $onConnectCallback */
    private $onConnectCallback;

    /** @var callable $onConnectCallback */
    private $onMessageCallback;

    public function __construct(StreamSelectLoop $loop, $host, $port, $path) {
        $this->setHost($host)
                ->setPort($port)
                ->setPath($path);
        $this->setSocket(new WebSocketConnection($this, $loop, $this->getHost(), $this->getPort(), $this->getPath()));
    }

    /**
     * Wywolywane zaraz po nawiazaniu polaczenia
     * @todo 
     * @param array $data
     */
    public function onConnect() {
        if (DEV_MODE) {
            print_r(["event" => "onConnect"]);
        }
        if ($this->onConnectCallback instanceof Closure) {
            $closure = $this->onConnectCallback;
            $closure($this);
        }
    }

    public function onMessage($data) {
        if ($this->onMessageCallback instanceof Closure) {
            $closure = $this->onMessageCallback;
            $closure($this, $data);
        } else {
            if (isset($data['type'])) {
                switch ($data['type']) {
                    case 'request':
                        $this->onRequest($data);
                        break;
                    case 'response':
                        $this->onResponse($data);
                        break;
                    default:
                        break;
                }
            }
        }
    }

    /**
     * Wywolywane gdy request pochodzi z serwera
     * @param type $data
     */
    public function onRequest($data) {
        $data = (array) $data;
        if (DEV_MODE) {
            print_r(["event" => "onRequest", "data" => $data]);
        }
        if (isset($data['request']['method'])) {
            if (method_exists(WebSocketClient::$requestHandler, $data['request']['method'])) {
                $return = call_user_func_array([WebSocketClient::$requestHandler, $data['request']['method']], [$data['request']['args']]);
                $this->send(MessageFactory::createResponse('ok', ['id' => $data['id'], 'data' => $return]));
                return true;
            }
        }
        $this->send(MessageFactory::createResponse('error', ['id' => $data['id'], 'data' => 'No method found']));
        return false;
    }

    /**
     * Wywolywane gdy dostaniemy odpowiedz z serwera
     * @param type $data
     */
    public function onResponse($response) {
        if (DEV_MODE) {
            print_r(["event" => "onResponse", "response" => $response]);
        }
//        if (isset(WebSocketClient::$callbacks[$response['id']])) {
//            call_user_func_array(WebSocketClient::$callbacks[$response['id']], $response);
//        }
        WebSocketClient::$iResponses++;
    }

    /**
     * Statystyki
     * @return string
     */
    public static function onTick() {
        if (DEV_MODE) {
            print_r(["event" => "onTick"]);
        }
        $ret = "\n" . WebSocketClient::$iResponses . " responses, " . WebSocketClient::$iRequests . " requests in " . LOOP_TIME . " seconds";
        WebSocketClient::$iResponses = 0;
        WebSocketClient::$iRequests = 0;
        return $ret;
    }

    /**
     * @param callable $callback
     * @return self
     */
    public function setOnMessageCallback(Closure $callback) {
        $this->onMessageCallback = $callback;
        return $this;
    }

    /**
     * @param callable $callback
     * @return self
     */
    public function setOnConnectCallback(Closure $callback) {
        $this->onConnectCallback = $callback;
        return $this;
    }

    /**
     * Wysylanie requestu
     * @param type $data
     * @static
     */
    public function send($data, &$callback = null) {
        WebSocketClient::$iRequests++;
        if (DEV_MODE) {
            print_r(["requestSent" => $data, 'callback' => $callback]);
        }
        if (isset($callback) && is_callable($callback)) {
            WebSocketClient::$callbacks[$data['id']] &= $callback;
            $result = $this->client->send($data);
//$result =$this->client->call($data['id'], $data, $callback);
        } else {
            $result = $this->client->send($data);
        }
        return $result;
    }

    public function disconnect() {

        $result = $this->client->disconnect();
        return $result;
    }

    public function setClient(WebSocketConnection $client) {
        $this->client = $client;
    }

    /**
     * @param string $host
     * @return self
     */
    public function setHost($host) {
        $this->host = (string) $host;
        return $this;
    }

    /**
     * @return string
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * @param string $path
     * @return self
     */
    public function setPath($path) {
        $this->path = (string) $path;
        return $this;
    }

    /**
     * @return string
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * @param int $port
     * @return self
     */
    public function setPort($port) {
        $this->port = (int) $port;
        return $this;
    }

    /**
     * @return int
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * @param WebSocketConnection $socket
     * @return self
     */
    public function setSocket(WebSocketConnection $socket) {
        $this->socket = $socket;
        return $this;
    }

    /**
     * @return WebSocketConnection
     */
    public function getSocket() {
        return $this->socket;
    }

}
