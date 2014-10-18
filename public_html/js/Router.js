var App = App || {};

App.Router = function(_, jQuery, promise, util, appState, presenterManager) {

	var root = '#/';
	var previousLocation = window.location.href;
	var routes = [];

	injectRoutes();

	function injectRoutes() {
		inject('', 'homePresenter');
		inject('#/', 'homePresenter');
		inject('#/404', 'httpErrorPresenter', {error: 404});
		inject('#/home', 'homePresenter');
		inject('#/login', 'loginPresenter');
		inject('#/logout', 'logoutPresenter');
		inject('#/register', 'registrationPresenter');
		inject('#/upload', 'postUploadPresenter');
		inject('#/password-reset(/:token)', 'userActivationPresenter', {operation: 'passwordReset'});
		inject('#/activate(/:token)', 'userActivationPresenter', {operation: 'activation'});
		inject('#/users(/:!query)', 'userListPresenter');
		inject('#/user/:userName(/:tab)', 'userPresenter');
		inject('#/posts(/:!query)', 'postListPresenter');
		inject('#/post/:postNameOrId(/:!query)', 'postPresenter');
		inject('#/comments(/:!query)', 'globalCommentListPresenter');
		inject('#/tags(/:!query)', 'tagListPresenter');
		inject('#/tag/:tagName', 'tagPresenter');
		inject('#/help(/:tab)', 'helpPresenter');
	}

	function navigate(url, useBrowserDispatcher) {
		if (('pushState' in history) && !useBrowserDispatcher) {
			history.pushState('', '', url);
			dispatch();
		} else {
			window.location.href = url;
		}
	}

	function navigateToMainPage() {
		navigate(root);
	}

	function navigateInplace(url, useBrowserDispatcher) {
		if ('replaceState' in history) {
			history.replaceState('', '', url);
			if (!useBrowserDispatcher) {
				dispatch();
			} else {
				location.reload();
			}
		} else {
			navigate(url, useBrowserDispatcher);
		}
	}

	function start() {
		if ('onhashchange' in window) {
			window.onhashchange = dispatch;
		} else {
			window.onpopstate = dispatch;
		}
		dispatch();
	}

	function inject(definition, presenterName, additionalParams) {
		routes.push(new Route(definition, function(params) {
			if (util.isExitConfirmationEnabled()) {
				if (window.location.href === previousLocation) {
					return;
				} else {
					if (window.confirm('Are you sure you want to leave this page? Data will be lost.')) {
						util.disableExitConfirmation();
					} else {
						window.location.href = previousLocation;
						return;
					}
				}
			}

			params = _.extend({}, params, additionalParams, {previousLocation: previousLocation});

			//abort every operation that can be executed
			promise.abortAll();
			previousLocation = window.location.href;

			var presenter = App.DI.get(presenterName);
			presenter.name = presenterName;
			presenterManager.switchContentPresenter(presenter, params);
		}));
	}

	function dispatch() {
		var url = document.location.hash;
		for (var i = 0; i < routes.length; i ++) {
			var route = routes[i];
			if (route.match(url)) {
				route.callback(route.params);
				return true;
			}
		}
		navigateInplace('#/404', true);
		return false;
	}

	function parseComplexParamValue(value) {
		var result = {};
		var params = (value || '').split(/;/);
		for (var i = 0; i < params.length; i ++) {
			var param = params[i];
			if (!param) {
				continue;
			}
			var kv = param.split(/=/);
			result[kv[0]] = kv[1];
		}
		return result;
	}

	function Route(definition, callback) {
		var possibleRoutes = getPossibleRoutes(definition);

		function getPossibleRoutes(routeDefinition) {
			var parts = [];
			var re = new RegExp('\\(([^}]+?)\\)', 'g');
			while (true) {
				var text = re.exec(routeDefinition);
				if (!text) {
					break;
				}
				parts.push(text[1]);
			}
			var possibleRoutes = [routeDefinition.split('(')[0]];
			for (var i = 0; i < parts.length; i ++) {
				possibleRoutes.push(possibleRoutes[possibleRoutes.length - 1] + parts[i]);
			}
			return possibleRoutes;
		}

		function match(url) {
			var params = {};
			for (var i = 0; i < possibleRoutes.length; i ++) {
				var possibleRoute = possibleRoutes[i];
				var compare = url;
				var possibleRouteParts = possibleRoute.split('/');
				var compareParts = compare.split('/');
				if (possibleRoute.search(':') > 0) {
					for (var j = 0; j < possibleRouteParts.length; j ++) {
						if ((j < compareParts.length) && (possibleRouteParts[j].charAt(0) === ':')) {
							var key = possibleRouteParts[j].substring(1);
							var value = compareParts[j];
							if (key.charAt(0) === '!') {
								key = key.substring(1);
								value = parseComplexParamValue(value);
							}
							params[key] = value;
							compare = compare.replace(compareParts[j], possibleRouteParts[j]);
						}
					}
				}
				if (possibleRoute === compare) {
					this.params = params;
					return true;
				}
			}
			return false;
		}

		this.match = match;
		this.callback = callback;
	}


	return {
		start: start,
		navigate: navigate,
		navigateInplace: navigateInplace,
		navigateToMainPage: navigateToMainPage,
	};
};

App.DI.registerSingleton('router', ['_', 'jQuery', 'promise', 'util', 'appState', 'presenterManager'], App.Router);
