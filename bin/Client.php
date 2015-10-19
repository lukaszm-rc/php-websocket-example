#!/bin/bash 
<?php
include "../vendor/autoload.php";
//clude "loader.php";

use WebSocket\Client\WebSocketConnection;
use WebSocketClient\WebSocketClient;
/**
 * EventLoop
 */
$loop = \React\EventLoop\Factory::create();
$client = new WebSocketClient($loop, '127.0.0.1', '8080','/');

/**
 * Podobne do setInterval() z Javascriptu
 */
$loop->addPeriodicTimer(LOOP_TIME, function () {
	echo WebSocketClient::onTick();
});
$loop->run();