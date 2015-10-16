<?php

include "../vendor/autoload.php";
include "../src/WebSocketClient/Functions.php";

use WebSocketClient\WebSocketClient;
use WebSocketClient\WebSocketClientInterface;
use WebSocketClient\WebSocketMessageFactory;

define('DEV_MODE', true);
define("LOOP_TIME", 1);

/**
 * Implementacja zdarzen klienta.
 *
 * @author Lukasz Mazurek <lukasz.mazurek@redcart.pl>
 */
class WebSocketObject implements WebSocketClientInterface {

	public static $client;

	public static $messageFactory;

	public static $requestHandler;

	public static $iResponses = 0;

	public static $iRequests = 0;

	public static $paused = false;

	/** @var callable $onWelcomeCallback */
	private $onWelcomeCallback;

	/** @var callable $onWelcomeCallback */
	private $onEventCallback;

	/** @var callable $onWelcomeCallback */
	private $onMessageCallback;

	public function __construct() {
//		WebSocketObject::$messageFactory = new WebSocketMessageFactory();
//		WebSocketObject::$requestHandler = new ServerRequestHandler();
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
		WebSocketObject::$messageFactory = new WebSocketMessageFactory();
		WebSocketObject::$requestHandler = new ServerRequestHandler();
	}

	public function onMessage($data) {
		if (isset($data['type'])) {
			switch ($data['type']) {
				case 'request':
					$this->onRequest($data);
					break;
				case 'response':
					$this->onResponse($data);
					break;
				case 'stop':
					$this->onStop($data);
					break;
				case 'resume':
					$this->onResume($data);
					break;
				default:
					break;
			}
		}
	}
    /**
     * @param array $data
     * @return void
     */
    public function onWelcome(array $data)
    {
        if ($this->onWelcomeCallback instanceof Closure) {
            $closure = $this->onWelcomeCallback;
            $closure($this, $data);
        }
    }

    /**
     * @param string $topic
     * @param array $data
     * @return void
     */
    public function onEvent($topic, $data)
    {
        if ($this->onEventCallback instanceof Closure) {
            $closure = $this->onEventCallback;
            $closure($this, $topic, $data);
        }
    }
	
	    /**
     * @param callable $callback
     * @return self
     */
    public function setOnWelcomeCallback(Closure $callback)
    {   
        $this->onWelcomeCallback = $callback;
        return $this;
    }

    /**
     * @param callable $onEventCallback
     * @return self
     */
    public function setOnEventCallback(Closure $onEventCallback)
    {
        $this->onEventCallback = $onEventCallback;
        return $this;
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
			if (method_exists(WebSocketObject::$requestHandler, $data['request']['method'])) {
				$return = call_user_func_array([WebSocketObject::$requestHandler, $data['request']['method']], [$data['request']['args']]);
				WebSocketObject::send(WebSocketObject::$messageFactory->createResponse('ok', ['id' => $data['id'], 'data' => $return]));
				return true;
			}
		}
		WebSocketObject::send(WebSocketObject::$messageFactory->createResponse('error', ['id' => $data['id'], 'data' => 'No method found']));
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
//		if (isset(WebSocketObject::$callbacks[$response['id']])) {
//			call_user_func_array(WebSocketObject::$callbacks[$response['id']], $response);
//		}
		WebSocketObject::$iResponses++;
	}

	/**
	 * Wywolywane przez serwer
	 * @param type $data
	 */
	public function onStop($data) {
		if (DEV_MODE) {
			print_r(["event" => "onStop", "data" => $data]);
		}
		WebSocketObject::$paused = true;
		// WebSocketObject::$client->disconnect();
		echo "\nStopping requests, waiting for signals...";
		// die();
	}

	/**
	 * Wywolywane przez serwer
	 * @param type $data
	 */
	public function onResume($data) {
		if (DEV_MODE) {
			print_r(["event" => "onResume", "data" => $data]);
		}
		WebSocketObject::$paused = false;
		WebSocketObject::send(['type' => 'request', 'args' => ["ping"]]);
		echo "\nResuming requests, waiting for signals...";
	}

	/**
	 * Statystyki
	 * @return string
	 */
	public static function onTick() {
		if (DEV_MODE) {
			print_r(["event" => "onTick"]);
		}
		$ret = "\n" . WebSocketObject::$iResponses . " responses, " . WebSocketObject::$iRequests . " requests in " . LOOP_TIME . " seconds";
		WebSocketObject::$iResponses = 0;
		WebSocketObject::$iRequests = 0;
		return $ret;
	}

	public function setClient(WebSocketClient $client) {
		WebSocketObject::$client = $client;
	}

	/**
	 * Wysylanie requestu
	 * @param type $data
	 * @static
	 */
	public static function send($data, &$callback = null) {
		WebSocketObject::$iRequests++;
		if (DEV_MODE) print_r(["requestSent" => $data, 'callback' => $callback]);
		if (isset($callback) && is_callable($callback)) {
			$result = WebSocketObject::$client->call($callback);
		}
		else {
			$result = WebSocketObject::$client->send($data);
		}
	}

}

/**
 * EventLoop
 */
$loop = React\EventLoop\Factory::create();
$client = new WebSocketClient(new WebSocketObject, $loop, '192.168.0.226', '8080');

/**
 * Podobne do setInterval() z Javascriptu
 */
$loop->addPeriodicTimer(LOOP_TIME, function () {
	if (WebSocketObject::$paused) {
		
	}
	else {
		
	}
	echo WebSocketObject::onTick();
});
$loop->run();
