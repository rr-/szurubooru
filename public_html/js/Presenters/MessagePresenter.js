var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.MessagePresenter = function(jQuery) {

	function showInfo($el, message) {
		return showMessage($el, 'info', message);
	};

	function showError($el, message) {
		return showMessage($el, 'error', message);
	};

	function hideMessages($el) {
		$el.children('.message').each(function() {
			$(this).slideUp('fast', function() {
				$(this).remove();
			});
		});
	};

	function showMessage($el, className, message) {
		var $messageDiv = $('<div>');
		$messageDiv.addClass('message');
		$messageDiv.addClass(className);
		$messageDiv.html(message);
		$messageDiv.hide();
		$el.append($messageDiv);
		$messageDiv.slideDown('fast');
		return $messageDiv;
	};

	return {
		showInfo: showInfo,
		showError: showError,
		hideMessages: hideMessages,
	};

};

App.DI.register('messagePresenter', App.Presenters.MessagePresenter);
