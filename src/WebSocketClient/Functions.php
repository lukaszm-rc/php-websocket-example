<?php

/**
 * @author Lukasz Mazurek <lukasz.mazurek@redcart.pl>
 */

namespace WebSocketClient;

define('SHELL_CORE_PATH', realpath(__DIR__ . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR);

spl_autoload_register(function ($className) {
    $fileName = implode(DIRECTORY_SEPARATOR, [
        SHELL_CORE_PATH,str_replace("\\", DIRECTORY_SEPARATOR, $className) . '.php'
    ]);
	echo $fileName."\n";
    if (file_exists($fileName)) {
        if (is_readable($fileName)) {
            require_once $fileName;
        } else {

        }
    } else {

    }
});
