var App = App || {};

App.Keyboard = function(jQuery, mousetrap, browsingSettings) {

	var enabled = browsingSettings.getSettings().keyboardShortcuts;
	var oldStopCallback = mousetrap.stopCallback;
	mousetrap.stopCallback = function(e, element, combo, sequence) {
		if (combo.indexOf('ctrl') === -1 && e.ctrlKey) {
			return true;
		}
		if (combo.indexOf('alt') === -1 && e.altKey) {
			return true;
		}
		if (combo.indexOf('ctrl') !== -1) {
			return false;
		}
		var $focused = jQuery(':focus').eq(0);
		if ($focused.length) {
			if ($focused.prop('tagName').match(/embed|object/i)) {
				return true;
			}
			if ($focused.prop('tagName').toLowerCase() === 'input' &&
				$focused.attr('type').match(/checkbox|radio/i)) {
				return false;
			}
		}
		return oldStopCallback.apply(mousetrap, arguments);
	};

	function keyup(key, callback) {
		unbind(key);
		if (enabled) {
			mousetrap.bind(key, callback, 'keyup');
		}
	}

	function keydown(key, callback) {
		unbind(key);
		if (enabled) {
			mousetrap.bind(key, callback);
		}
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

App.DI.register('keyboard', ['jQuery', 'mousetrap', 'browsingSettings'], App.Keyboard);
