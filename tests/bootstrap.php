<?php

ini_set('include_path', ini_get('include_path'));
define('SHELL_CORE_PATH', realpath(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR));

spl_autoload_register(function ($className) {
	$fileName = implode(DIRECTORY_SEPARATOR, [
		SHELL_CORE_PATH, "src", str_replace("\\", DIRECTORY_SEPARATOR, $className) . '.php'
	]);
	if (file_exists($fileName)) {
		if (is_readable($fileName)) {
			require_once $fileName;
		}
		else {
		}
	}
	else {
		
	}
});

include SHELL_CORE_PATH . "/vendor/autoload.php";

define("SERVER_IP", "127.0.0.1");
define("SERVER_PORT", "8080");
define("SERVER_PATH", "/");
