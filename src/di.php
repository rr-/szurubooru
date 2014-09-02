<?php
return [
	\Szurubooru\Config::class => DI\object()->constructor([
		__DIR__ . DS . '..' . DS . 'data' . DS . 'config.ini',
		__DIR__ . DS . '..' . DS . 'data' . DS . 'local.ini']),

	\Szurubooru\ControllerRepository::class => DI\object()->constructor(DI\link('controllers')),

	'controllers' => DI\factory(function (DI\container $c) {
		return [
			$c->get(\Szurubooru\Controllers\AuthController::class),
			$c->get(\Szurubooru\Controllers\UserController::class),
		];
	}),
];
