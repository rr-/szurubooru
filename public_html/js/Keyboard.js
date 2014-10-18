var App = App || {};

App.Keyboard = function(mousetrap) {

	function keyup(key, callback) {
		unbind(key);
		mousetrap.bind(key, callback, 'keyup');
	}

	function keydown(key, callback) {
		unbind(key);
		mousetrap.bind(key, callback);
	}

	function reset() {
		mousetrap.reset();
	}

	function unbind(key) {
		mousetrap.unbind(key, 'keyup');
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
