<?php

namespace WebSocketClient;

/**
 * Struktura wiadomosci przesylane miedzy klientem a serwerem.
 *
 * @author Lukasz Mazurek <lukasz.mazurek@redcart.pl>
 */
class MessageFactory {

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

}
