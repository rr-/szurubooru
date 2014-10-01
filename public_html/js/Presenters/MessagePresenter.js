var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.MessagePresenter = function(_, jQuery) {

	var options = {
		instant: false
	};

	function showInfo($el, message) {
		return showMessage($el, 'info', message);
	}

	function showError($el, message) {
		return showMessage($el, 'error', message);
	}

	function hideMessages($el) {
		var $messages = $el.children('.message');
		if (options.instant) {
			$messages.each(function() {
				jQuery(this).slideUp('fast', function() {
					jQuery(this).remove();
				});
			});
		} else {
			$messages.remove();
		}
	}

	function showMessage($el, className, message) {
		var $messageDiv = jQuery('<div>');
		$messageDiv.addClass('message');
		$messageDiv.addClass(className);
		$messageDiv.html(message);
		if (!options.instant) {
			$messageDiv.hide();
		}
		$el.append($messageDiv);
		if (!options.instant) {
			$messageDiv.slideDown('fast');
		}
		return $messageDiv;
	}

	return _.extend(options, {
		showInfo: showInfo,
		showError: showError,
		hideMessages: hideMessages,
	});

};

App.DI.register('messagePresenter', ['_', 'jQuery'], App.Presenters.MessagePresenter);
