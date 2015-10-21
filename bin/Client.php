#!/usr/bin/php 
<?php
include "../vendor/autoload.php";

use WebSocketClient\WebSocketClient;
use WebSocketClient\MessageFactory;

/**
 * EventLoop
 */
$loop = \React\EventLoop\Factory::create();
$client = new WebSocketClient($loop, '127.0.0.1', '8080', '/');

/**
 * Podobne do setInterval() z Javascriptu
 */
$loop->addPeriodicTimer(3, function () use ($client) {
	WebSocketClient::onTick();
	$client->send(MessageFactory::createRequest(WebSocketClient::$iRequests, "ping"));
});
$loop->run();
