<?php

namespace WebSocketClient;

/**
 * Struktura wiadomosci przesylane miedzy klientem a serwerem.
 *
 * @author Lukasz Mazurek <lukasz.mazurek@redcart.pl>
 */
class MessageFactory {

	const TYPE_ID_WELCOME = 0;

	const TYPE_ID_REQUEST = 1;

	const TYPE_ID_RESPONSE = 2;

	const TYPE_ID_CALL = 3;

	const TYPE_ID_CALLRESULT = 4;

	const TYPE_ID_ERROR = 5;

	const TYPE_ID_EVENT = 6;

	private $json = '{ 
        "id" : "rand",
        "type" :
        "request|response",
        "request" : {
                "method" : "", "args" : []
            },
       "response" : {
            "time" : 123.123,
         "status" : "ok|error",
         "data" : {},
          "error" : "message"
        }
      }';

	private $array;

	public function __construct($json = null) {
		$this->array = json_decode(($json != null?$json:$this->json), true);
	}

	public static function createRequest($id, $method, $args = null) {
		$msg = [
			'id' => $id,
			'type' => 'request',
			'request' => [
				'method' => $method,
				'args' => $args
			]
		];
		return $msg;
	}

	public static function createResponse($status, $data) {
		$msg = [
			'id' => $data['id'],
			'type' => 'response',
			'response' => [
				'time' => microtime(true),
				'status' => $status,
			]
		];
		if ($status == 'ok') {
			$msg['response']['data'] = $data['data'];
		}
		else {
			$msg['response']['error'] = $data['data'];
		}
		return $msg;
	}

	public function __get($name) {
		if (in_array($name, array_keys($this->array))) {
			return $this->array[$name];
		}
	}

	public function __set($name, $value) {
		if (in_array($name, array_keys($this->array))) {
			return $this->array[$name] = $value;
		}
	}

	public function __isset($name) {
		return isset($this->array[$name]);
	}

	public function getArray() {
		return $this->array;
	}

	public function getJSON() {
		return json_encode($this->array);
	}

}
