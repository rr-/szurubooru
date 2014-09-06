<?php
$dataDirectory = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data';
return [
	\Szurubooru\Config::class => DI\object()->constructor([
		$dataDirectory . DIRECTORY_SEPARATOR . 'config.ini',
		$dataDirectory . DIRECTORY_SEPARATOR . 'local.ini']),

	\Szurubooru\Services\FileService::class => DI\object()->constructor($dataDirectory),

	\Szurubooru\ControllerRepository::class => DI\object()->constructor(DI\link('controllers')),

	'controllers' => DI\factory(function (DI\container $c) {
		return [
			$c->get(\Szurubooru\Controllers\AuthController::class),
			$c->get(\Szurubooru\Controllers\UserController::class),
			$c->get(\Szurubooru\Controllers\UserAvatarController::class),
		];
	}),
];
