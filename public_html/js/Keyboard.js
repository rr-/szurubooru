var App = App || {};

App.Keyboard = function(mousetrap) {

	var oldStopCallback = mousetrap.stopCallback;
	mousetrap.stopCallback = function(e, element, combo, sequence) {
		if (combo.indexOf('ctrl') !== -1) {
			return false;
		}
		return oldStopCallback.apply(mousetrap, arguments);
	};

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
