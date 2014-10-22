var App = App || {};
App.Controls = App.Controls || {};

App.Presenters.ProgressPresenter = function(nprogress) {
	var nesting = 0;

	function start() {
		nesting ++;

		if (nesting === 1) {
			nprogress.start();
		}
	}

	function done() {
		nesting --;

		if (nesting === 0) {
			nprogress.done();
		} else {
			nprogress.inc();
		}
	}

	return {
		start: start,
		done: done,
	};

};

App.DI.registerSingleton('progress', ['nprogress'], App.Presenters.ProgressPresenter);
