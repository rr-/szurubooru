<?php
$dataDirectory = __DIR__
	. DIRECTORY_SEPARATOR . '..'
	. DIRECTORY_SEPARATOR . 'data';

$publicDataDirectory = __DIR__
	. DIRECTORY_SEPARATOR . '..'
	. DIRECTORY_SEPARATOR . 'public_html'
	. DIRECTORY_SEPARATOR . 'data';

return [
	\Szurubooru\Config::class => DI\object()->constructor($dataDirectory, $publicDataDirectory),

	\Szurubooru\ControllerRepository::class => DI\object()->constructor(DI\link('controllers')),
	\Szurubooru\Upgrades\UpgradeRepository::class => DI\object()->constructor(DI\link('upgrades')),

	'upgrades' => DI\factory(function (DI\container $container) {
		return [
			$container->get(\Szurubooru\Upgrades\Upgrade01::class),
			$container->get(\Szurubooru\Upgrades\Upgrade02::class),
			$container->get(\Szurubooru\Upgrades\Upgrade03::class),
			$container->get(\Szurubooru\Upgrades\Upgrade04::class),
			$container->get(\Szurubooru\Upgrades\Upgrade05::class),
			$container->get(\Szurubooru\Upgrades\Upgrade06::class),
			$container->get(\Szurubooru\Upgrades\Upgrade07::class),
			$container->get(\Szurubooru\Upgrades\Upgrade08::class),
			$container->get(\Szurubooru\Upgrades\Upgrade09::class),
			$container->get(\Szurubooru\Upgrades\Upgrade10::class),
			$container->get(\Szurubooru\Upgrades\Upgrade11::class),
			$container->get(\Szurubooru\Upgrades\Upgrade12::class),
			$container->get(\Szurubooru\Upgrades\Upgrade13::class),
			$container->get(\Szurubooru\Upgrades\Upgrade14::class),
			$container->get(\Szurubooru\Upgrades\Upgrade15::class),
			$container->get(\Szurubooru\Upgrades\Upgrade16::class),
			$container->get(\Szurubooru\Upgrades\Upgrade17::class),
			$container->get(\Szurubooru\Upgrades\Upgrade18::class),
			$container->get(\Szurubooru\Upgrades\Upgrade19::class),
			$container->get(\Szurubooru\Upgrades\Upgrade20::class),
			$container->get(\Szurubooru\Upgrades\Upgrade21::class),
			$container->get(\Szurubooru\Upgrades\Upgrade22::class),
			$container->get(\Szurubooru\Upgrades\Upgrade23::class),
			$container->get(\Szurubooru\Upgrades\Upgrade24::class),
			$container->get(\Szurubooru\Upgrades\Upgrade25::class),
			$container->get(\Szurubooru\Upgrades\Upgrade26::class),
		];
	}),

	'controllers' => DI\factory(function (DI\container $container) {
		return [
			$container->get(\Szurubooru\Controllers\AuthController::class),
			$container->get(\Szurubooru\Controllers\UserController::class),
			$container->get(\Szurubooru\Controllers\UserAvatarController::class),
			$container->get(\Szurubooru\Controllers\PostController::class),
			$container->get(\Szurubooru\Controllers\PostContentController::class),
			$container->get(\Szurubooru\Controllers\GlobalParamController::class),
			$container->get(\Szurubooru\Controllers\HistoryController::class),
			$container->get(\Szurubooru\Controllers\FavoritesController::class),
			$container->get(\Szurubooru\Controllers\ScoreController::class),
			$container->get(\Szurubooru\Controllers\CommentController::class),
			$container->get(\Szurubooru\Controllers\TagController::class),
		];
	}),
];
