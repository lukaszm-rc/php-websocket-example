<?php

namespace WebSocketDemo;

use \Closure;
use \WebSocket\Client\WebSocketClientInterface;
use \WebSocket\Client\WebSocketConnection;
use \React\EventLoop\StreamSelectLoop;
use \WebSocketDemo\Libs\MessageFactory;
use \WebSocketDemo\Libs\RequestHandler;
use \React\EventLoop\LoopInterface;

define('DEV_MODE', false);
define("LOOP_TIME", 5);

/**
 * Implementacja zdarzen klienta.
 *
 * @author Lukasz Mazurek <lukasz.mazurek@redcart.pl>
 */
class ClientEvents implements WebSocketClientInterface {

    public static $iResponses = 0;
    public static $iRequests = 0;
    public $requests = 0;
    public $responses = 0;
    private $requestHandler;

    /** @var callable $onConnectCallback */
    private $onConnectCallback;

    /** @var callable $onConnectCallback */
    private $onMessageCallback;

    public function __construct(LoopInterface $loop, $host, $port, $path) {
	$this -> setHost($host);
	$this -> setPort($port);
	$this -> setPath($path);
	$this -> socket = new WebSocketConnection($this, $loop, $this -> getHost(), $this -> getPort(), $this -> getPath());
	$this -> requestHandler = new RequestHandler();
	//return $this;
    }

    /**
     * Wywolywane zaraz po nawiazaniu polaczenia
     * @todo
     * @param array $data
     */
    public function onConnect() {
//		if (DEV_MODE) {
//			print_r(["event" => "onConnect"]);
//		}
	if ($this -> onConnectCallback instanceof Closure) {
	    $closure = $this -> onConnectCallback;
	    $closure($this);
	}
    }

    public function onMessage($data) {
	$this -> responses++;
	if ($this -> onMessageCallback instanceof Closure) {
	    $closure = $this -> onMessageCallback;
	    $closure($this, $data);
	} else {
	    if (isset($data[ 'type' ])) {
		switch ($data[ 'type' ]) {
		    case 'request':
			$this -> onRequest($data);
			break;
		    case 'response':
			$this -> onResponse($data);
			break;
		    default:
			return false;
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
//		if (DEV_MODE) {
//			print_r(["event" => "onRequest", "data" => $data]);
//		}
	if (isset($data[ 'request' ][ 'method' ])) {
	    if (method_exists($this -> requestHandler, $data[ 'request' ][ 'method' ])) {
		$return = call_user_func_array([$this -> requestHandler, $data[ 'request' ][ 'method' ] ], [$data[ 'request' ][ 'args' ] ]);
		$this -> send(MessageFactory::createResponse('ok', ['id' => $data[ 'id' ], 'data' => $return ]));
		return true;
	    }
	}
	$this -> send(MessageFactory::createResponse('error', ['id' => $data[ 'id' ], 'data' => 'No method found' ]));
	return false;
    }

    /**
     * Wywolywane gdy dostaniemy odpowiedz z serwera
     * @param type $data
     */
    public function onResponse($response) {
//		if (DEV_MODE) {
//			print_r(["event" => "onResponse", "response" => $response]);
//		}
	$this -> send(MessageFactory::createRequest(ClientEvents::$iRequests, "ping"));
	ClientEvents::$iResponses++;
//		echo "+";
    }

    /**
     * Statystyki
     * @return string
     */
    public static function onTick() {
//		if (DEV_MODE) {
//			print_r(["event" => "onTick"]);
//		}
	$ret = "\n" . ClientEvents::$iResponses . " responses, " . ClientEvents::$iRequests . " requests in " . LOOP_TIME . " seconds";
	ClientEvents::$iResponses = 0;
	return $ret;
    }

    /**
     * @param callable $callback
     * @return self
     */
    public function setOnMessageCallback(Closure $callback) {
	$this -> onMessageCallback = $callback;
	return $this;
    }

    /**
     * @param callable $callback
     * @return self
     */
    public function setOnConnectCallback(Closure $callback) {
	$this -> onConnectCallback = $callback;
	return $this;
    }

    /**
     * Wysylanie requestu
     * @param type $data
     * @static
     */
    public function send($data, &$callback = null) {

	if (!$this -> socket -> send($data)) {
	    echo "Lost connection\n";
	    $this->socket->disconnect();
	    return false;
	}
	ClientEvents::$iRequests++;
	$this -> requests++;
	return true;
    }

    public function disconnect() {

	$result = $this -> socket -> disconnect();
	return $result;
    }

    /**
     * @param string $host
     * @return self
     */
    public function setHost($host) {
	$this -> host = (string) $host;
    }

    /**
     * @return string
     */
    public function getHost() {
	return $this -> host;
    }

    /**
     * @param string $path
     * @return self
     */
    public function setPath($path) {
	$this -> path = (string) $path;
    }

    /**
     * @return string
     */
    public function getPath() {
	return $this -> path;
    }

    /**
     * @param int $port
     * @return self
     */
    public function setPort($port) {
	$this -> port = (int) $port;
    }

    /**
     * @return int
     */
    public function getPort() {
	return $this -> port;
    }

    /**
     * @param WebSocketConnection $socket
     * @return self
     */
    public function setSocket(WebSocketConnection $socket) {
	$this -> socket = $socket;
    }

    /**
     * @return WebSocketConnection
     */
    public function getSocket() {
	return $this -> socket;
    }

}
