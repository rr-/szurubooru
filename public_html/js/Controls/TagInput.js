var App = App || {};
App.Controls = App.Controls || {};

App.Controls.TagInput = function($underlyingInput) {
	var _ = App.DI.get('_');
	var jQuery = App.DI.get('jQuery');

	var KEY_RETURN = 13;
	var KEY_SPACE = 32;
	var KEY_BACKSPACE = 8;
	var tagConfirmKeys = [KEY_RETURN, KEY_SPACE];
	var inputConfirmKeys = [KEY_RETURN];

	var tags = [];
	var options = {
		beforeTagAdded: null,
		beforeTagRemoved: null,
		inputConfirmed: null,
	};

	if ($underlyingInput.length === 0) {
		throw new Error('Tag input element was not found');
	}
	if ($underlyingInput.length > 1) {
		throw new Error('Cannot set tag input to more than one element at once');
	}
	if ($underlyingInput.attr('data-tagged')) {
		throw new Error('Tag input was already initialized for this element');
	}
	$underlyingInput.attr('data-tagged', true);
	$underlyingInput.hide();

	var $wrapper = jQuery('<div class="tag-input">');
	var $tagList = jQuery('<ul class="tags">');
	var $input = jQuery('<input class="tag-real-input" type="text"/>');
	$wrapper.append($tagList);
	$wrapper.append($input);
	$wrapper.insertAfter($underlyingInput);
	$wrapper.click(function(e) {
		if (e.target.nodeName === 'LI') {
			return;
		}
		e.preventDefault();
		$input.focus();
	});
	$input.attr('placeholder', $underlyingInput.attr('placeholder'));

	addTagsFromText($underlyingInput.val());
	$underlyingInput.val('');

	initAutocomplete();

	function initAutocomplete() {
		var autocomplete = new App.Controls.AutoCompleteInput($input);
		autocomplete.onApply = function(text) {
			addTagsFromText(text);
			$input.val('');
		};
		autocomplete.additionalFilter = function(results) {
			var tags = getTags();
			return _.filter(results, function(resultItem) {
				return !_.contains(tags, resultItem[0]);
			});
		};
	}

	$input.bind('focus', function(e) {
		$wrapper.addClass('focused');
	});
	$input.bind('blur', function(e) {
		$wrapper.removeClass('focused');
		var tag = $input.val();
		addTag(tag);
		$input.val('');
	});

	$input.bind('paste', function(e) {
		e.preventDefault();
		var pastedText;
		if (window.clipboardData) {
			pastedText = window.clipboardData.getData('Text');
		} else {
			pastedText = (e.originalEvent || e).clipboardData.getData('text/plain');
		}

		if (pastedText.length > 200) {
			window.alert('Pasted text is too long.');
			return;
		}

		var pastedTags = pastedText.split(/\s+/);
		var lastTag = pastedTags.pop();
		_.map(pastedTags, addTag);
		$input.val(lastTag);
	});

	$input.bind('keydown', function(e) {
		if (_.contains(inputConfirmKeys, e.which) && !$input.val()) {
			e.preventDefault();
			if (typeof(options.inputConfirmed) !== 'undefined') {
				options.inputConfirmed();
			}
		} else if (_.contains(tagConfirmKeys, e.which)) {
			var tag = $input.val();
			e.preventDefault();
			$input.val('');
			addTag(tag);
		} else if (e.which === KEY_BACKSPACE && jQuery(this).val().length === 0) {
			e.preventDefault();
			removeLastTag();
		}
	});

	function addTagsFromText(text) {
		var tagsToAdd = text.split(/\s+/);
		_.map(tagsToAdd, addTag);
	}

	function addTag(tag) {
		tag = tag.trim();
		if (tag.length === 0) {
			return;
		}

		if (tag.length > 64) {
			//showing alert inside keydown event leads to mysterious behaviors
			//in some browsers, hence the timeout
			window.setTimeout(function() {
				window.alert('Tag is too long.');
			}, 10);
			return;
		}

		var oldTags = getTags();
		if (_.contains(_.map(oldTags, function(tag) { return tag.toLowerCase(); }), tag.toLowerCase())) {
			flashTag(tag);
		} else {
			if (typeof(options.beforeTagAdded) === 'function') {
				options.beforeTagAdded(tag);
			}
			var newTags = oldTags.slice();
			newTags.push(tag);
			setTags(newTags);
		}
	}

	function removeTag(tag) {
		var oldTags = getTags();
		var newTags = _.without(oldTags, tag);
		if (newTags.length !== oldTags.length) {
			if (typeof(options.beforeTagRemoved) === 'function') {
				options.beforeTagRemoved(tag);
			}
			setTags(newTags);
		}
	}

	function removeLastTag() {
		removeTag(_.last(getTags()));
	}

	function flashTag(tag) {
		var $elem = $tagList.find('li[data-tag="' + tag.toLowerCase() + '"]');
		$elem.css({backgroundColor: 'rgba(255, 200, 200, 1)'});
	}

	function setTags(newTags) {
		tags = newTags.slice();
		$tagList.empty();
		$underlyingInput.val(newTags.join(' '));
		_.each(newTags, function(tag) {
			var $elem = jQuery('<li/>');
			$elem.text(tag);
			$elem.attr('data-tag', tag.toLowerCase());

			var $deleteButton = jQuery('<a><i class="fa fa-remove"></i></a>');
			$deleteButton.bind('click', function(e) {
				e.preventDefault();
				removeTag(tag);
				$input.focus();
			});
			$elem.append($deleteButton);

			$tagList.append($elem);
		});
	}

	function getTags() {
		return tags;
	}

	function focus() {
		$input.focus();
	}

	_.extend(options, {
		setTags: setTags,
		getTags: getTags,
		removeTag: removeTag,
		addTag: addTag,
		focus: focus,
	});
	return options;
};
