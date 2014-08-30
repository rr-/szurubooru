<?php
return [
	\Szurubooru\Config::class => DI\object()->constructor([
		__DIR__ . DS . 'config.ini',
		__DIR__ . DS . 'local.ini']),
];
