var App = App || {};

App.PresenterManager = function(topNavigationPresenter) {

	var lastContentPresenterName;
	var lastContentPresenter;

	function init() {
		initPresenter('topNavigationPresenter');
	}

	function initPresenter(presenterName, args) {
		var presenter = App.DI.get(presenterName);
		presenter.init.call(presenter, args);
	}

	function switchContentPresenter(presenterName, args) {
		if (lastContentPresenterName !== presenterName) {
			topNavigationPresenter.changeTitle(null);
			var presenter = App.DI.get(presenterName);
			presenter.init.call(presenter, args);
			lastContentPresenterName = presenterName;
			lastContentPresenter = presenter;
		} else if (lastContentPresenter.reinit) {
			lastContentPresenter.reinit.call(lastContentPresenter, args);
		}
	}

	return {
		init: init,
		switchContentPresenter: switchContentPresenter,
	};

};

App.DI.registerSingleton('presenterManager', ['topNavigationPresenter'], App.PresenterManager);
