var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.MessagePresenter = function(jQuery) {

	function showInfo($el, message) {
		return showMessage($el, 'info', message);
	}

	function showError($el, message) {
		return showMessage($el, 'error', message);
	}

	function hideMessages($el) {
		$el.children('.message').each(function() {
			jQuery(this).slideUp('fast', function() {
				jQuery(this).remove();
			});
		});
	}

	function showMessage($el, className, message) {
		var $messageDiv = jQuery('<div>');
		$messageDiv.addClass('message');
		$messageDiv.addClass(className);
		$messageDiv.html(message);
		$messageDiv.hide();
		$el.append($messageDiv);
		$messageDiv.slideDown('fast');
		return $messageDiv;
	}

	return {
		showInfo: showInfo,
		showError: showError,
		hideMessages: hideMessages,
	};

};

App.DI.register('messagePresenter', ['jQuery'], App.Presenters.MessagePresenter);
