<?php

namespace WebSocketClient;

/**
 * Description of functions
 *
 * @author Lukasz Mazurek <lukasz.mazurek@redcart.pl>
 */
interface WebSocketClientInterface {

	public function onConnect();

	public function onMessage($data);

	public function onRequest($data);

	public function onResponse($data);

	public function setClient(WebSocketClient $client);
}
