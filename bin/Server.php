#!/usr/bin/php
<?php
include "../vendor/autoload.php";
/**
 * EventLoop
 */
use WebSocket\Server\WebSocketServer;

$events = new WebSocketDemo\ServerEvents();
$server = WebSocketServer::Factory($events, "127.0.0.1", "8080");

/**
 * Podobne do setInterval() z Javascriptu
 */
$server->loop->addPeriodicTimer(5, function () use (&$events) {
	echo $events->getStatus(5);
});
$server->loop->addPeriodicTimer(60, function () use (&$server) {
	echo $server->loop->stop();
});
$server->run();
