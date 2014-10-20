var App = App || {};

App.PresenterManager = function(jQuery, promise, topNavigationPresenter, keyboard, progress) {

	var lastContentPresenter = null;

	function init() {
		return promise.make(function(resolve, reject) {
			initPresenter(topNavigationPresenter, [], resolve);
		});
	}

	function initPresenter(presenter, args, loaded) {
		presenter.init.call(presenter, args, loaded);
	}

	function switchContentPresenter(presenter, args) {
		progress.start();

		if (lastContentPresenter === null || lastContentPresenter.name !== presenter.name) {
			if (lastContentPresenter !== null && lastContentPresenter.deinit) {
				lastContentPresenter.deinit();
			}
			keyboard.reset();
			topNavigationPresenter.changeTitle(null);
			topNavigationPresenter.focus();
			presenter.init.call(presenter, args, progress.done);
			lastContentPresenter = presenter;
		} else if (lastContentPresenter.reinit) {
			lastContentPresenter.reinit.call(lastContentPresenter, args, progress.done);
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

App.DI.registerSingleton('presenterManager', ['jQuery', 'promise', 'topNavigationPresenter', 'keyboard', 'progress'], App.PresenterManager);
