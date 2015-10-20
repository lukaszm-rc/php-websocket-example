#!/bin/bash
composer install
composer require "phpunit/phpunit" "4.8.*"
composer update
composer dump-autoload