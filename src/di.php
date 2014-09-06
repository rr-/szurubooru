<?php
return [
	\Szurubooru\Config::class => DI\object()->constructor([
		__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'config.ini',
		__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'local.ini']),

	\Szurubooru\ControllerRepository::class => DI\object()->constructor(DI\link('controllers')),

	'controllers' => DI\factory(function (DI\container $c) {
		return [
			$c->get(\Szurubooru\Controllers\AuthController::class),
			$c->get(\Szurubooru\Controllers\UserController::class),
		];
	}),
];
