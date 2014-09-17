var App = App || {};

App.PresenterManager = function(jQuery, topNavigationPresenter) {

	var lastContentPresenter = null;
	var $spinner;
	var spinnerTimeout;

	function init() {
		initPresenter(topNavigationPresenter, [], function() {});
		$spinner = jQuery('body').find('#wait');
	}

	function initPresenter(presenter, args, loaded) {
		presenter.init.call(presenter, args, loaded);
	}

	function switchContentPresenter(presenter, args) {
		var contentPresenterLoaded = function() {
			window.clearTimeout(spinnerTimeout);
			$spinner.hide();
		};

		spinnerTimeout = window.setTimeout(function() {
			$spinner.stop().fadeIn('slow');
		}, 300);

		if (lastContentPresenter === null || lastContentPresenter.name !== presenter.name) {
			topNavigationPresenter.changeTitle(null);
			presenter.init.call(presenter, args, contentPresenterLoaded);
			lastContentPresenter = presenter;
		} else if (lastContentPresenter.reinit) {
			lastContentPresenter.reinit.call(lastContentPresenter, args, contentPresenterLoaded);
		}
	}

	function initPresenters(options, loaded) {
		var count = 0;
		var subPresenterLoaded = function() {
			count ++;
			if (count === options.length) {
				loaded();
			}
		};

		for (var i = 0; i < options.length; i ++) {
			initPresenter(options[i][0], options[i][1], subPresenterLoaded);
		}
	}

	return {
		init: init,
		initPresenter: initPresenter,
		initPresenters: initPresenters,
		switchContentPresenter: switchContentPresenter,
	};

};

App.DI.registerSingleton('presenterManager', ['jQuery', 'topNavigationPresenter'], App.PresenterManager);
