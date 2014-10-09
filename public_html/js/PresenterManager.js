var App = App || {};

App.PresenterManager = function(jQuery, promise, topNavigationPresenter, keyboard) {

	var lastContentPresenter = null;
	var $spinner;
	var spinnerTimeout;

	function init() {
		$spinner = jQuery('body').find('#wait');
		return promise.make(function(resolve, reject) {
			initPresenter(topNavigationPresenter, [], resolve);
		});
	}

	function initPresenter(presenter, args, loaded) {
		presenter.init.call(presenter, args, loaded);
	}

	function showContentSpinner() {
		if (spinnerTimeout !== null) {
			spinnerTimeout = window.setTimeout(function() {
				$spinner.show();
			}, 150);
		}
	}

	function hideContentSpinner() {
		window.clearTimeout(spinnerTimeout);
		spinnerTimeout = null;
		$spinner.hide();
	}

	function switchContentPresenter(presenter, args) {
		showContentSpinner();

		if (lastContentPresenter === null || lastContentPresenter.name !== presenter.name) {
			if (lastContentPresenter !== null && lastContentPresenter.deinit) {
				lastContentPresenter.deinit();
			}
			keyboard.reset();
			topNavigationPresenter.changeTitle(null);
			topNavigationPresenter.focus();
			presenter.init.call(presenter, args, hideContentSpinner);
			lastContentPresenter = presenter;
		} else if (lastContentPresenter.reinit) {
			lastContentPresenter.reinit.call(lastContentPresenter, args, hideContentSpinner);
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
		showContentSpinner: showContentSpinner,
		hideContentSpinner: hideContentSpinner,
	};

};

App.DI.registerSingleton('presenterManager', ['jQuery', 'promise', 'topNavigationPresenter', 'keyboard'], App.PresenterManager);
