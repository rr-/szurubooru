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

	function reset() {
		nesting = 0;
	}

	function done() {
		if (nesting) {
			nesting --;
		}

		if (nesting <= 0) {
			nprogress.done();
		} else {
			nprogress.inc();
		}
	}

	window.setInterval(function() {
		if (nesting <= 0) {
			nprogress.done();
		}
	}, 1000);

	return {
		start: start,
		done: done,
		reset: reset,
	};

};

App.DI.registerSingleton('progress', ['nprogress'], App.Presenters.ProgressPresenter);
