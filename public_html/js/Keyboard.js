var App = App || {};

App.Keyboard = function(mousetrap) {

	function keyup(key, callback) {
		mousetrap.bind(key, simpleKeyPressed(callback), 'keyup');
	}

	function keydown(key, callback) {
		mousetrap.bind(key, simpleKeyPressed(callback));
	}

	function simpleKeyPressed(callback) {
		return function(e) {
			if (!e.altKey && !e.ctrlKey) {
				callback();
			}
		};
	}

	function reset() {
		mousetrap.reset();
	}

	function unbind(key) {
		mousetrap.unbind(key);
	}

	return {
		keydown: keydown,
		keyup: keyup,
		reset: reset,
		unbind: unbind,
	};
};

App.DI.register('keyboard', ['mousetrap'], App.Keyboard);
