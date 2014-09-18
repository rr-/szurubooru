var App = App || {};

App.Router = function(pathJs, _, jQuery, util, appState, presenterManager) {

	var root = '#/';
	var previousLocation = window.location.href;

	injectRoutes();

	function navigateToMainPage() {
		window.location.href = root;
	}

	function navigate(url) {
		window.location.href = url;
	}

	function navigateInplace(url) {
		if ('replaceState' in history) {
			history.replaceState('', '', url);
			pathJs.dispatch(document.location.hash);
		} else {
			navigate(url);
		}
	}

	function start() {
		pathJs.listen();
	}

	function injectRoutes() {
		inject('#/home', 'homePresenter');
		inject('#/login', 'loginPresenter');
		inject('#/logout', 'logoutPresenter');
		inject('#/register', 'registrationPresenter');
		inject('#/upload', 'postUploadPresenter');
		inject('#/password-reset(/:token)', 'userActivationPresenter', {operation: 'passwordReset'});
		inject('#/activate(/:token)', 'userActivationPresenter', {operation: 'activation'});
		inject('#/users(/:searchArgs)', 'userListPresenter');
		inject('#/user/:userName(/:tab)', 'userPresenter');
		inject('#/posts(/:searchArgs)', 'postListPresenter');
		inject('#/post(/:postNameOrId)', 'postPresenter');
		inject('#/comments(/:searchArgs)', 'commentListPresenter');
		inject('#/tags(/:searchArgs)', 'tagListPresenter');
		inject('#/help', 'helpPresenter');
		setRoot('#/home');
	}

	function setRoot(newRoot) {
		root = newRoot;
		pathJs.root(newRoot);
	}

	function inject(path, presenterName, additionalParams) {
		pathJs.map(path).to(function() {
			var finalParams = _.extend(
				this.params,
				additionalParams,
				{previousRoute: pathJs.routes.previous});

			var presenter = App.DI.get(presenterName);
			presenter.name = presenterName;
			presenterManager.switchContentPresenter(presenter, finalParams);
		}).enter(function(e) {
			if (util.isExitConfirmationEnabled()) {
				if (window.location.href !== previousLocation) {
					if (!window.confirm('Are you sure you want to leave this page? Data will be lost.')) {
						window.location.href = previousLocation;
						return false;
					} else {
						util.disableExitConfirmation();
					}
				}
			}
			previousLocation = window.location.href;
		});
	}

	return {
		start: start,
		navigate: navigate,
		navigateInplace: navigateInplace,
		navigateToMainPage: navigateToMainPage,
	};

};

App.DI.registerSingleton('router', ['pathJs', '_', 'jQuery', 'util', 'appState', 'presenterManager'], App.Router);
