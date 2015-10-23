<?php

/**
 * Description of MessageHandler
 *
 * @author Lukasz Mazurek <lukasz.mazurek@redcart.pl>
 */
namespace WebSocketDemo;
use \WebSocket\Server\ServerConnection;
use \WebSocket\Request\Request;
use \WebSocket\Request\Response;
use \WebSocketDemo\Libs\MessageFactory;
use \WebSocketDemo\Libs\RequestHandler;
use \WebSocket\Server\WebSocketServerInterface;

class ServerEvents implements WebSocketServerInterface {

	private $clients;

	protected $connections = 0;

	protected $inRequests = 0;

	protected $inResponses = 0;

	protected $outRequests = 0;

	protected $outResponses = 0;

	protected $_inRequests = 0;

	protected $_inResponses = 0;

	protected $_outRequests = 0;

	protected $_outResponses = 0;

	protected $onMessageCallback = null;

	protected $requestHandler;

	public function __construct() {
		$this->requestHandler = new RequestHandler();
		$this->clients = new \SplObjectStorage;
	}

	public function getStatus($interval) {
		$inRPS = $this->inRequests - $this->_inRequests;
		$inRespPS = $this->inResponses - $this->_inResponses;
		$outRPS = $this->outRequests - $this->_outRequests;
		$outRespPS = $this->outResponses - $this->_outResponses;
		$str = sprintf(
				  "Received requests: %s (%s per second)\n"
				. "Received responses: %s  (%s per second)\n"
				. "Sent requests: %s  (%s per second)\n"
				. "Sent responses: %s  (%s per second)\n"
				. "\n", $this->inRequests, ($inRPS / $interval), $this->inResponses, ($inRespPS / $interval), $this->outRequests, ($outRPS / $interval), $this->outResponses, ($outRespPS / $interval));
		$this->statistics();
		return $str;
	}

	private function statistics() {
		$this->_inRequests = $this->inRequests;
		$this->_inResponses = $this->inResponses;
		$this->_outRequests = $this->outRequests;
		$this->_outResponses = $this->outResponses;
	}

	public function onOpen(ServerConnection $conn) {
		$this->message("Client " . $conn->resourceId . " connected");
		$conn->WebSocket = new \StdClass;
		$conn->WebSocket->established = false;
		$conn->WebSocket->closing = false;
		$this->clients->attach($conn);
	}

	public function establishConnection($client, $message) {
		$connected = false;
		if (count($this->clients) > 0) {
			foreach ($this->clients as $_client) {
				if ($_client->resourceId == $client->resourceId && $_client->WebSocket->established) {
					$connected = true;
				}
			}
		}
		if (!$connected) {
			$handshake = $this->handshake($message);
			$client->send($handshake);
			$client->WebSocket->established = true;
			$this->clients->attach($client);
			return false;
		}
		return true;
	}

	public function handshake($message) {
		return new Response(101, array (
			'Sec-WebSocket-Protocol' => 'wamp',
			'Upgrade' => 'websocket'
			, 'Connection' => 'Upgrade'
			, 'Sec-WebSocket-Accept' => Request::getSign((string) Request::getHeaders($message)['Sec-WebSocket-Key'])
		));
	}

	public function onClose(ServerConnection $client) {
		$this->clients->detach($client);
		$this->message("Client " . $client->resourceId . " disconnected");
	}

	public function onError(ServerConnection $client, $e) {
		echo "-";
		print_r($e);
	}

	protected function message($message) {
		$status = "[ current connections " . count($this->clients) . " ]";
		echo $message . " " . $status . "\n";
	}

	public function onMessage(ServerConnection $client, $_data) {

		if ($this->onMessageCallback instanceof Closure) {
			$closure = $this->onMessageCallback;
			$closure($this, $_data);
		}
		else {
			if ($this->establishConnection($client, $_data)) {
				//$message = Request::getMessage(Request::parseIncomingRaw($message));
				$this->inRequests++;
				$message = Request::getMessage($_data);
//				echo join(",", array_keys($message));
				if (isset($message['type'])) {
					$data = $message;
//					echo "+";
					if (isset($data['type'])) {
						switch ($data['type']) {
							case 'request':
								$this->onRequest($client, $data);
								break;
							case 'response':
								$this->onResponse($client, $data);
								break;
							default:
								return false;
								break;
						}
					}
				}
			}
		}
	}

	/**
	 * Wywolywane gdy request pochodzi z serwera
	 * @param type $data
	 */
	public function onRequest(&$client, $data) {
		$data = (array) $data;
		if (isset($data['request']['method'])) {
			if (method_exists($this->requestHandler, $data['request']['method'])) {
				$return = call_user_func_array([$this->requestHandler, $data['request']['method']], [$data['request']['args']]);
				$msg = MessageFactory::createResponse('ok', ['id' => $data['id'], 'data' => $return]);
			}
		}
		if (!isset($msg)) {
			$msg = MessageFactory::createResponse('error', ['id' => $data['id'], 'data' => 'No method found']);
		}
		$this->outResponses++;
		$client->send(json_encode($msg));
		return false;
	}

	/**
	 * Wywolywane gdy dostaniemy odpowiedz z serwera
	 * @param type $data
	 */
	public function onResponse($response) {
		$this->inResponses++;
	}

}
