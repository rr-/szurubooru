<?php
\Chibi\Router::register(['StaticPagesController', 'mainPageView'], 'GET', '');
\Chibi\Router::register(['StaticPagesController', 'mainPageView'], 'GET', '/index');
\Chibi\Router::register(['StaticPagesController', 'helpView'], 'GET', '/help');
\Chibi\Router::register(['StaticPagesController', 'helpView'], 'GET', '/help/{tab}');

\Chibi\Router::register(['AuthController', 'loginView'], 'GET', '/auth/login');
\Chibi\Router::register(['AuthController', 'loginAction'], 'POST', '/auth/login');
\Chibi\Router::register(['AuthController', 'logoutAction'], 'POST', '/auth/logout');
\Chibi\Router::register(['AuthController', 'logoutAction'], 'GET', '/auth/logout');

\Chibi\Router::register(['LogController', 'listView'], 'GET', '/logs');
\Chibi\Router::register(['LogController', 'logView'], 'GET', '/log/{name}', ['name' => '[0-9a-zA-Z._-]+']);
\Chibi\Router::register(['LogController', 'logView'], 'GET', '/log/{name}/{page}', ['name' => '[0-9a-zA-Z._-]+', 'page' => '\d*']);
\Chibi\Router::register(['LogController', 'logView'], 'GET', '/log/{name}/{page}/{filter}', ['name' => '[0-9a-zA-Z._-]+', 'page' => '\d*', 'filter' => '.*']);

$postValidation =
[
	'tag' => '[^\/]*',
	'enable' => '0|1',
	'source' => 'posts|mass-tag',
	'query' => '[^\/]*',
	'additionalInfo' => '[^\/]*',
	'score' => '-1|0|1',
];

\Chibi\Router::register(['PostController', 'uploadView'], 'GET', '/posts/upload', $postValidation);
\Chibi\Router::register(['PostController', 'uploadAction'], 'POST', '/posts/upload', $postValidation);
\Chibi\Router::register(['PostController', 'editView'], 'GET', '/post/{id}/edit', $postValidation);
\Chibi\Router::register(['PostController', 'editAction'], 'POST', '/post/{id}/edit', $postValidation);
\Chibi\Router::register(['PostController', 'deleteAction'], 'POST', '/post/{id}/delete', $postValidation);

\Chibi\Router::register(['PostController', 'listView'], 'GET', '/{source}', $postValidation);
\Chibi\Router::register(['PostController', 'listView'], 'GET', '/{source}/{query}', $postValidation);
\Chibi\Router::register(['PostController', 'listView'], 'GET', '/{source}/{query}/{page}', $postValidation);
\Chibi\Router::register(['PostController', 'listView'], 'GET', '/{source}/{additionalInfo}/{query}/{page}', $postValidation);

\Chibi\Router::register(['PostController', 'randomView'], 'GET', '/random', $postValidation);
\Chibi\Router::register(['PostController', 'randomView'], 'GET', '/random/{page}', $postValidation);
\Chibi\Router::register(['PostController', 'favoritesView'], 'GET', '/favorites', $postValidation);
\Chibi\Router::register(['PostController', 'favoritesView'], 'GET', '/favorites/{page}', $postValidation);
\Chibi\Router::register(['PostController', 'upvotedView'], 'GET', '/upvoted', $postValidation);
\Chibi\Router::register(['PostController', 'upvotedView'], 'GET', '/upvoted/{page}', $postValidation);

\Chibi\Router::register(['PostController', 'genericView'], 'GET', '/post/{id}', $postValidation);
\Chibi\Router::register(['PostController', 'fileView'], 'GET', '/post/{name}/retrieve', $postValidation);
\Chibi\Router::register(['PostController', 'thumbView'], 'GET', '/post/{name}/thumb', $postValidation);

\Chibi\Router::register(['PostController', 'toggleTagAction'], 'POST', '/post/{id}/toggle-tag/{tag}/{enable}', $postValidation);
\Chibi\Router::register(['PostController', 'flagAction'], 'POST', '/post/{id}/flag', $postValidation);
\Chibi\Router::register(['PostController', 'hideAction'], 'POST', '/post/{id}/hide', $postValidation);
\Chibi\Router::register(['PostController', 'unhideAction'], 'POST', '/post/{id}/unhide', $postValidation);
\Chibi\Router::register(['PostController', 'removeFavoriteAction'], 'POST', '/post/{id}/rem-fav', $postValidation);
\Chibi\Router::register(['PostController', 'addFavoriteAction'], 'POST', '/post/{id}/add-fav', $postValidation);
\Chibi\Router::register(['PostController', 'scoreAction'], 'POST', '/post/{id}/score/{score}', $postValidation);
\Chibi\Router::register(['PostController', 'featureAction'], 'POST', '/post/{id}/feature', $postValidation);

$commentValidation =
[
	'id' => '\d+',
	'page' => '\d+',
];

\Chibi\Router::register(['CommentController', 'listView'], 'GET', '/comments', $commentValidation);
\Chibi\Router::register(['CommentController', 'listView'], 'GET', '/comments/{page}', $commentValidation);
\Chibi\Router::register(['CommentController', 'addAction'], 'POST', '/comment/add', $commentValidation);
\Chibi\Router::register(['CommentController', 'deleteAction'], 'POST', '/comment/{id}/delete', $commentValidation);
\Chibi\Router::register(['CommentController', 'editView'], 'GET', '/comment/{id}/edit', $commentValidation);
\Chibi\Router::register(['CommentController', 'editAction'], 'POST', '/comment/{id}/edit', $commentValidation);

$tagValidation =
[
	'page' => '\d*',
	'filter' => '[^\/]+',
];

\Chibi\Router::register(['TagController', 'listView'], 'GET', '/tags', $tagValidation);
\Chibi\Router::register(['TagController', 'listView'], 'GET', '/tags/{page}', $tagValidation);
\Chibi\Router::register(['TagController', 'listView'], 'GET', '/tags/{filter}/{page}', $tagValidation);
\Chibi\Router::register(['TagController', 'autoCompleteView'], 'GET', '/tags-autocomplete', $tagValidation);
\Chibi\Router::register(['TagController', 'relatedView'], 'GET', '/tags-related', $tagValidation);
\Chibi\Router::register(['TagController', 'renameView'], 'GET', '/tags-rename', $tagValidation);
\Chibi\Router::register(['TagController', 'renameAction'], 'POST', '/tags-rename', $tagValidation);
\Chibi\Router::register(['TagController', 'mergeView'], 'GET', '/tags-merge', $tagValidation);
\Chibi\Router::register(['TagController', 'mergeAction'], 'POST', '/tags-merge', $tagValidation);
\Chibi\Router::register(['TagController', 'massTagRedirectView'], 'GET', '/mass-tag-redirect', $tagValidation);
\Chibi\Router::register(['TagController', 'massTagRedirectAction'], 'POST', '/mass-tag-redirect', $tagValidation);

$userValidation =
[
	'identifier' => '[^\/]+',
	'page' => '\d*',
	'tab' => 'favs|uploads|settings|edit|delete',
	'filter' => '[^\/]+',
];

\Chibi\Router::register(['UserController', 'listView'], 'GET', '/users', $userValidation);
\Chibi\Router::register(['UserController', 'listView'], 'GET', '/users/{page}', $userValidation);
\Chibi\Router::register(['UserController', 'listView'], 'GET', '/users/{filter}/{page}', $userValidation);
\Chibi\Router::register(['UserController', 'genericView'], 'GET', '/user/{identifier}/{tab}', $userValidation);
\Chibi\Router::register(['UserController', 'genericView'], 'GET', '/user/{identifier}/{tab}/{page}', $userValidation);

\Chibi\Router::register(['UserController', 'registrationView'], 'GET', '/register', $userValidation);
\Chibi\Router::register(['UserController', 'registrationAction'], 'POST', '/register', $userValidation);

\Chibi\Router::register(['UserController', 'activationView'], 'GET', '/activation', $userValidation);
\Chibi\Router::register(['UserController', 'activationAction'], 'POST', '/activation', $userValidation);
\Chibi\Router::register(['UserController', 'activationAction'], 'GET', '/activation/{tokenText}', $userValidation);
\Chibi\Router::register(['UserController', 'passwordResetView'], 'GET', '/password-reset', $userValidation);
\Chibi\Router::register(['UserController', 'passwordResetAction'], 'POST', '/password-reset', $userValidation);
\Chibi\Router::register(['UserController', 'passwordResetAction'], 'GET', '/password-reset/{tokenText}', $userValidation);

\Chibi\Router::register(['UserController', 'flagAction'], 'POST', '/user/{identifier}/flag', $userValidation);
\Chibi\Router::register(['UserController', 'banAction'], 'POST', '/user/{identifier}/ban', $userValidation);
\Chibi\Router::register(['UserController', 'unbanAction'], 'POST', '/user/{identifier}/unban', $userValidation);
\Chibi\Router::register(['UserController', 'acceptRegistrationAction'], 'POST', '/user/{identifier}/accept-registration', $userValidation);
\Chibi\Router::register(['UserController', 'deleteAction'], 'POST', '/user/{identifier}/delete', $userValidation);
\Chibi\Router::register(['UserController', 'settingsAction'], 'POST', '/user/{identifier}/settings', $userValidation);
\Chibi\Router::register(['UserController', 'editAction'], 'POST', '/user/{identifier}/edit', $userValidation);
\Chibi\Router::register(['UserController', 'toggleSafetyAction'], 'POST', '/user/toggle-safety/{safety}', $userValidation);
