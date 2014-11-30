var App = App || {};
App.Controls = App.Controls || {};

App.Controls.AutoCompleteInput = function($input) {
	var _ = App.DI.get('_');
	var jQuery = App.DI.get('jQuery');
	var tagList = App.DI.get('tagList');

	var KEY_RETURN = 13;
	var KEY_ESCAPE = 27;
	var KEY_UP = 38;
	var KEY_DOWN = 40;

	var options = {
		caseSensitive: false,
		source: null,
		maxResults: 15,
		minLengthToArbitrarySearch: 3,
		onApply: null,
		onRender: null,
		additionalFilter: null,
	};
	var showTimeout = null;
	var cachedSource = null;
	var results = [];
	var activeResult = -1;

	if ($input.length === 0) {
		throw new Error('Input element was not found');
	}
	if ($input.length > 1) {
		throw new Error('Cannot add autocompletion to more than one element at once');
	}
	if ($input.attr('data-autocomplete')) {
		throw new Error('Autocompletion was already added for this element');
	}
	$input.attr('data-autocomplete', true);
	$input.attr('autocomplete', 'off');

	var $div = jQuery('<div>');
	var $list = jQuery('<ul>');
	$div.addClass('autocomplete');
	$div.append($list);
	jQuery(document.body).append($div);

	function getSource() {
		if (cachedSource) {
			return cachedSource;
		} else {
			var source = tagList.getTags();
			source = _.sortBy(source, function(a) { return -a.usages; });
			source = _.filter(source, function(a) { return a.usages >= 0; });
			source = _.map(source, function(a) {
				return {
					tag: a.name,
					caption: a.name + ' (' + a.usages + ')',
				};
			});
			cachedSource = source;
			return source;
		}
	}

	$input.bind('keydown', function(e) {
		if (isShown() && e.which === KEY_ESCAPE) {
			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();
			hide();
		} else if (isShown() && e.which === KEY_DOWN) {
			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();
			selectNext();
		} else if (isShown() && e.which === KEY_UP) {
			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();
			selectPrevious();
		} else if (isShown() && e.which === KEY_RETURN && activeResult >= 0) {
			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();
			applyAutocomplete();
			hide();
		} else {
			window.clearTimeout(showTimeout);
			showTimeout = window.setTimeout(showOrHide, 250);
		}
	});

	$input.blur(function(e) {
		window.clearTimeout(showTimeout);
		window.setTimeout(function() { hide(); }, 50);
	});

	function getSelectionStart(){
		var input = $input.get(0);
		if (!input) {
			return;
		}
		if ('selectionStart' in input) {
			return input.selectionStart;
		} else if (document.selection) {
			input.focus();
			var sel = document.selection.createRange();
			var selLen = document.selection.createRange().text.length;
			sel.moveStart('character', -input.value.length);
			return sel.text.length - selLen;
		} else {
			return 0;
		}
	}

	function getTextToFind() {
		var val = $input.val();
		var start = getSelectionStart();
		return val.substring(0, start).replace(/.*\s/, '');
	}

	function showOrHide() {
		var textToFind = getTextToFind();
		if (textToFind.length === 0) {
			hide();
		} else {
			updateResults(textToFind);
			refreshList();
		}
	}

	function isShown() {
		return $div.is(':visible');
	}

	function hide() {
		$div.hide();
	}

	function selectPrevious() {
		select(activeResult === -1 ? results.length - 1 : activeResult - 1);
	}

	function selectNext() {
		select(activeResult === -1 ? 0 : activeResult + 1);
	}

	function select(newActiveResult) {
		if (newActiveResult >= 0 && newActiveResult < results.length) {
			activeResult = newActiveResult;
			refreshActiveResult();
		} else {
			activeResult = - 1;
			refreshActiveResult();
		}
	}

	function getResultsFilter(textToFind) {
		if (textToFind.length < options.minLengthToArbitrarySearch) {
			return options.caseSensitive ?
				function(resultItem) { return resultItem.tag.indexOf(textToFind) === 0; } :
				function(resultItem) { return resultItem.tag.toLowerCase().indexOf(textToFind.toLowerCase()) === 0; };
		} else {
			return options.caseSensitive ?
				function(resultItem) { return resultItem.tag.indexOf(textToFind) >= 0; } :
				function(resultItem) { return resultItem.tag.toLowerCase().indexOf(textToFind.toLowerCase()) >= 0; };
		}
	}

	function updateResults(textToFind) {
		var oldResults = results.slice();
		var source = getSource();
		var filter = getResultsFilter(textToFind);
		results = _.filter(source, filter);
		if (options.additionalFilter) {
			results = options.additionalFilter(results);
		}
		results = results.slice(0, options.maxResults);
		if (!_.isEqual(oldResults, results)) {
			activeResult = -1;
		}
	}

	function applyAutocomplete() {
		if (options.onApply) {
			options.onApply(results[activeResult].tag);
		} else {
			var val = $input.val();
			var start = getSelectionStart();
			var prefix = '';
			var suffix = val.substring(start);
			var middle = val.substring(0, start);
			var index = middle.lastIndexOf(' ');
			if (index !== -1) {
				prefix = val.substring(0, index + 1);
				middle = val.substring(index + 1);
			}
			$input.val(prefix + results[activeResult].tag + ' ' + suffix.trimLeft());
			$input.focus();
		}
	}

	function refreshList() {
		if (results.length === 0) {
			hide();
			return;
		}

		$list.empty();
		_.each(results, function(resultItem, resultIndex) {
			var $listItem = jQuery('<li/>');
			$listItem.text(resultItem.caption);
			$listItem.attr('data-key', resultItem.tag);
			$listItem.hover(function(e) {
				e.preventDefault();
				activeResult = resultIndex;
				refreshActiveResult();
			});
			$listItem.mousedown(function(e) {
				e.preventDefault();
				activeResult = resultIndex;
				applyAutocomplete();
				hide();
			});
			$list.append($listItem);
		});
		if (options.onRender) {
			options.onRender($list);
		}
		refreshActiveResult();
		$div.css({
			left: ($input.offset().left) + 'px',
			top: ($input.offset().top + $input.outerHeight() - 2) + 'px',
		});
		$div.show();
	}

	function refreshActiveResult() {
		$list.find('li.active').removeClass('active');
		if (activeResult >= 0) {
			$list.find('li').eq(activeResult).addClass('active');
		}
	}

	return options;
};
