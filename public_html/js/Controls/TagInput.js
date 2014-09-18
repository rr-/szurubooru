var App = App || {};
App.Controls = App.Controls || {};

//todo: autocomplete

App.Controls.TagInput = function(
	$underlyingInput,
	_,
	jQuery) {

	var KEY_RETURN = 13;
	var KEY_SPACE = 32;
	var KEY_BACKSPACE = 8;
	var tagConfirmKeys = [KEY_RETURN, KEY_SPACE];

	var tags = [];
	var options = {
		beforeTagAdded: null,
		beforeTagRemoved: null,
	};

	if ($underlyingInput.length !== 1) {
		throw new Error('Cannot set tag input to more than one elements at once');
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
		e.preventDefault();
		$input.focus();
	});
	$input.attr('placeholder', $underlyingInput.attr('placeholder'));

	$input.unbind('focus').bind('focus', function(e) {
		$wrapper.addClass('focused');
	});
	$input.unbind('blur').bind('blur', function(e) {
		$wrapper.removeClass('focused');
	});

	$input.unbind('paste').bind('paste', function(e) {
		e.preventDefault();
		var pastedText;
		if (window.clipboardData) {
			pastedText = window.clipboardData.getData('Text');
		} else {
			pastedText = (e.originalEvent || e).clipboardData.getData('text/plain');
		}
		var patedTags = pastedText.split(/\s+/);
		_.each(patedTags, function(tag) {
			addTag(tag);
		});
	});

	$input.unbind('keydown').bind('keydown', function(e) {
		if (_.contains(tagConfirmKeys, e.which)) {
			e.preventDefault();
			var tag = $input.val();
			addTag(tag);
			$input.val('');
		} else if (e.which === KEY_BACKSPACE && jQuery(this).val().length === 0) {
			e.preventDefault();
			removeLastTag();
		}
	});

	function addTag(tag) {
		tag = tag.trim();
		if (tag.length === 0) {
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

App.DI.register('tagInput', App.Controls.TagInput);
