#!/usr/bin/php 
<?php
include "../vendor/autoload.php";

use WebSocketDemo\Libs\MessageFactory;
use \WebSocketDemo\ClientEvents;
define("TEST_MODE", true);
/**
 * EventLoop
 */
$loop = \React\EventLoop\Factory::create();
$client = new ClientEvents($loop, '127.0.0.1', '8080', '/');

/**
 * Podobne do setInterval() z Javascriptu
 */
$loop->addPeriodicTimer(LOOP_TIME, function () use ($client) {
	echo ClientEvents::onTick();
	if (TEST_MODE) {
		$client->send(MessageFactory::createRequest(ClientEvents::$iRequests, "ping"));
	}
});

//$loop->addPeriodicTimer(60, function () use ($loop) {
//	$loop->stop();
//});
$loop->run();
