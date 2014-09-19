var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PostUploadPresenter = function(
	_,
	jQuery,
	keyboard,
	promise,
	util,
	auth,
	api,
	router,
	topNavigationPresenter,
	messagePresenter) {

	var $el = jQuery('#content');
	var $messages;
	var template;
	var allPosts = [];
	var tagInput;
	var interactionEnabled = true;

	function init(args, loaded) {
		topNavigationPresenter.select('upload');
		topNavigationPresenter.changeTitle('Upload');

		promise.wait(util.promiseTemplate('post-upload'))
			.then(function(html) {
				template = _.template(html);
				render();
				loaded();
			});
	}

	function render() {
		$el.html(template({
			canUploadPostsAnonymously: auth.hasPrivilege(auth.privileges.uploadPostsAnonymously)
		}));
		$messages = $el.find('.messages');

		tagInput = new App.Controls.TagInput($el.find('form [name=tags]'), _, jQuery);
		App.Controls.FileDropper($el.find('[name=post-content]'), fileHandlerChanged, jQuery);

		$el.find('.url-handler input').keydown(urlHandlerKeyPressed);
		$el.find('.url-handler button').click(urlHandlerButtonClicked);
		$el.find('thead th.checkbox').click(postTableSelectAllCheckboxClicked);

		keyboard.keyup('a', selectPrevPostTableRow);
		keyboard.keyup('d', selectNextPostTableRow);

		$el.find('.remove').click(removeButtonClicked);
		$el.find('.move-up').click(moveUpButtonClicked);
		$el.find('.move-down').click(moveDownButtonClicked);
		$el.find('.submit').click(submitButtonClicked);
	}

	function getDefaultPost() {
		return {
			safety: 'safe',
			source: null,
			anonymous: false,
			tags: [],

			fileName: null,
			content: null,
			url: null,
			thumbnail: null,
			$tableRow: null,
		};
	}

	function urlHandlerKeyPressed(e) {
		if (e.which !== 13) {
			return;
		}

		e.preventDefault();
		$el.find('.url-handler button').trigger('click');
	}

	function urlHandlerButtonClicked(e) {
		var $input = $el.find('.url-handler input');

		var url = $input.val().trim();
		if (url === '') {
			return;
		}

		var protocol = /^(\w+):\/\//.exec(url);
		if (!protocol) {
			url = 'http://' + url;
		} else {
			protocol = protocol[1].toLowerCase();
			if (protocol !== 'http' && protocol !== 'https') {
				window.alert('Unsupported protocol: ' + protocol);
				return;
			}
		}
		$input.val('');
		addPostFromUrl(url);
	}

	function fileHandlerChanged(files) {
		for (var i = 0; i < files.length; i ++) {
			addPostFromFile(files[i]);
		}
	}

	function addPostFromFile(file) {
		var post = _.extend({}, getDefaultPost(), {fileName: file.name});

		var reader = new FileReader();
		reader.onload = function(e) {
			post.content = e.target.result;
			if (file.type.match('image.*')) {
				post.thumbnail = e.target.result;
				postThumbnailLoaded(post);
			}
		};
		reader.readAsDataURL(file);

		postAdded(post);
	}

	function addPostFromUrl(url) {
		var post = _.extend({}, getDefaultPost(), {url: url, source: url, fileName: url});

		var matches = url.match(/watch.*?=([a-zA-Z0-9_-]+)/);
		if (matches) {
			var youtubeThumbnailUrl = 'http://img.youtube.com/vi/' + matches[1] + '/mqdefault.jpg';
			post.thumbnail = youtubeThumbnailUrl;
		} else {
			post.thumbnail = url;
		}
		postAdded(post);
		postThumbnailLoaded(post);
	}

	function postAdded(post) {
		var allPosts = getAllPosts();
		allPosts.push(post);
		setAllPosts(allPosts);
		createPostTableRow(post);
	}

	function postChanged(post) {
		updatePostTableRow(post);
	}

	function createPostTableRow(post) {
		var $table = $el.find('table');
		var $row = $table.find('.template').clone(true);

		post.$tableRow = $row;

		$row.removeClass('template');
		$row.find('td:not(.checkbox)').click(postTableRowClicked);
		$row.find('td.checkbox').click(postTableCheckboxClicked);
		$row.find('img').mouseenter(postTableRowImageHovered);
		$row.find('img').mouseleave(postTableRowImageUnhovered);
		$row.data('post', post);
		$table.find('tbody').append($row);

		postChanged(post);

		selectPostTableRow(post);
		showOrHidePostsTable();
	}

	function updatePostTableRow(post) {
		var $row = post.$tableRow;
		$row.find('.tags').text(post.tags.join(', ') || '-');
		$row.find('.safety div').attr('class', 'safety-' + post.safety);
	}

	function postThumbnailLoaded(post) {
		var selectedPosts = getSelectedPosts();
		if (selectedPosts.length === 1 && selectedPosts[0] === post && post.thumbnail !== null) {
			updatePostThumbnailInForm(post);
		}
		updatePostThumbnailInTable(post);
	}

	function updatePostThumbnailInForm(post) {
		$el.find('.form-slider .thumbnail img')[0].setAttribute('src', post.thumbnail);
	}

	function updatePostThumbnailInTable(post) {
		var $row = post.$tableRow;
		//huge speedup thanks to this condition
		if ($row.find('img').attr('src') !== post.thumbnail && post.thumbnail !== null) {
			$row.find('img')[0].setAttribute('src', post.thumbnail);
		}
	}

	function postTableRowClicked(e) {
		e.preventDefault();
		var $allCheckboxes = jQuery(this).parents('table').find('tbody input[type=checkbox]');
		var $myCheckbox = jQuery(this).parents('tr').find('input[type=checkbox]');
		$allCheckboxes.prop('checked', false);
		$myCheckbox.prop('checked', true);
		postTableCheckboxesChanged(e);
	}

	function postTableCheckboxClicked(e) {
		if (e.target.nodeName === 'TD') {
			var checkbox = jQuery(this).find('input[type=checkbox]');
			checkbox.prop('checked', !checkbox.prop('checked'));
		}
		postTableCheckboxesChanged(e);
	}

	function postTableSelectAllCheckboxClicked(e) {
		var $checkbox = jQuery(this).find('input[type=checkbox]');
		if (e.target.nodeName === 'TH') {
			$checkbox.prop('checked', !$checkbox.prop('checked'));
		}
		$el.find('tbody input[type=checkbox]').prop('checked', $checkbox.prop('checked'));
		postTableCheckboxesChanged();
	}

	function postTableCheckboxesChanged(e) {
		var $table = $el.find('table');
		if (!interactionEnabled) {
			if (typeof(e) !== 'undefined') {
				e.preventDefault();
			}
			return;
		}

		$table.find('tbody tr').each(function(i, row) {
			var $row = jQuery(row);
			var checked = $row.find('input[type=checkbox]').prop('checked');
			$row.toggleClass('selected', checked);
		});

		var allPosts = getAllPosts();
		var selectedPosts = getSelectedPosts();
		$table.find('[name=select-all]').prop('checked', allPosts.length === selectedPosts.length);
		postTableSelectionChanged(selectedPosts);
	}

	function getAllPosts() {
		return allPosts;
	}

	function setAllPosts(newPosts) {
		allPosts = newPosts;
	}

	function setAllPostsFromTable() {
		var newPosts = _.map($el.find('tbody tr'), function(row) {
			var $row = jQuery(row);
			return $row.data('post');
		});
		setAllPosts(newPosts);
	}

	function getSelectedPosts() {
		var selectedPosts = [];
		$el.find('tbody tr.selected').each(function(i, row) {
			var $row = jQuery(row);
			selectedPosts.push($row.data('post'));
		});
		return selectedPosts;
	}

	function postTableSelectionChanged(selectedPosts) {
		messagePresenter.hideMessages($messages);
		if (selectedPosts.length === 0) {
			hidePostEditForm();
		} else {
			showPostEditForm(selectedPosts);
			tagInput.focus();
		}
		$el.find('.post-table-op').prop('disabled', selectedPosts.length === 0);
	}

	function hidePostEditForm() {
		var $postEditForm = $el.find('form');
		$postEditForm.parent('.form-slider').slideUp(function() {
			$postEditForm.find('.thumbnail').hide();
		});
	}

	function showPostEditForm(selectedPosts) {
		var $postEditForm = $el.find('form');
		$postEditForm.parent('.form-slider').slideDown();
		if (selectedPosts.length !== 1) {
			$postEditForm.parent('.form-slider').find('.thumbnail').slideUp();
			$postEditForm.find('.file-name strong').text('Multiple posts selected');
		} else {
			var post = selectedPosts[0];
			$postEditForm.parent('.form-slider').find('.thumbnail').slideDown();
			$postEditForm.find('.file-name strong').text(post.fileName || post.url);
			updatePostThumbnailInForm(post);
		}

		var combinedPost = getCombinedPost(selectedPosts);

		$postEditForm.find('[name=source]').val(combinedPost.source);
		$postEditForm.find('[name=anonymous]').prop('checked', combinedPost.anonymous);
		$postEditForm.find('[name=safety]').prop('checked', false);
		if (combinedPost.safety !== null) {
			$postEditForm.find('[name=safety][value=' + combinedPost.safety + ']').prop('checked', true);
		}
		tagInput.setTags(combinedPost.tags);

		$postEditForm.find('[name=source]').unbind('change').bind('change', function(e) {
			setPostsSource(selectedPosts, jQuery(this).val());
		});
		$postEditForm.find('[name=safety]').unbind('change').bind('change', function(e) {
			setPostsSafety(selectedPosts, jQuery(this).val());
		});
		$postEditForm.find('[name=anonymous]').unbind('change').bind('change', function(e) {
			setPostsAnonymity(selectedPosts, jQuery(this).is(':checked'));
		});
		tagInput.beforeTagAdded = function(tag) {
			addTagToPosts(selectedPosts, tag);
		};
		tagInput.beforeTagRemoved = function(tag) {
			removeTagFromPosts(selectedPosts, tag);
		};
	}

	function getCombinedPost(posts) {
		var combinedPost = _.extend({}, getDefaultPost());
		if (posts.length === 0) {
			return combinedPost;
		}
		_.extend(combinedPost, posts[0]);

		var tagFilter = function(post) {
			return function(tag) {
				return post.tags.indexOf(tag) !== -1;
			};
		};

		for (var i = 1; i < posts.length; i ++) {
			if (posts[i].safety !== posts[0].safety) {
				combinedPost.safety = null;
			}
			if (posts[i].anonymous !== posts[0].anonymous) {
				combinedPost.anonymous = null;
			}
			if (posts[i].source !== posts[0].source) {
				combinedPost.source = null;
			}
			combinedPost.tags = combinedPost.tags.filter(tagFilter(posts[i]));
		}

		return combinedPost;
	}

	function setPostsSource(posts, newSource) {
		_.each(posts, function(post) {
			//todo: take care of max source length
			post.source = newSource;
			postChanged(post);
		});
	}

	function setPostsSafety(posts, newSafety) {
		_.each(posts, function(post) {
			post.safety = newSafety;
			postChanged(post);
		});
	}

	function setPostsAnonymity(posts, isAnonymous) {
		_.each(posts, function(post) {
			post.anonymous = isAnonymous;
			postChanged(post);
		});
	}

	function addTagToPosts(posts, tag) {
		jQuery.each(posts, function(i, post) {
			var index = post.tags.indexOf(tag);
			if (index === -1) {
				post.tags.push(tag);
			}
			postChanged(post);
		});
	}

	function removeTagFromPosts(posts, tag) {
		jQuery.each(posts, function(i, post) {
			var index = post.tags.indexOf(tag);
			if (index !== -1) {
				post.tags.splice(index, 1);
			}
			postChanged(post);
		});
	}

	function postTableRowImageHovered(e) {
		var $img = jQuery(this);
		if ($img.attr('src')) {
			var $lightbox = jQuery('#lightbox');
			$lightbox.find('img').attr('src', $img.attr('src'));
			$lightbox
				.show()
				.css({
					left: ($img.position().left + $img.outerWidth()) + 'px',
					top: ($img.position().top + ($img.outerHeight() - $lightbox.outerHeight()) / 2) + 'px',
				});
		}
	}

	function postTableRowImageUnhovered(e) {
		var $lightbox = jQuery('#lightbox');
		$lightbox.hide();
	}

	function selectPostTableRow(post) {
		if (post) {
			var $table = $el.find('table');
			$table.find('tbody input[type=checkbox]').prop('checked', false);
			$table.find('tbody tr').each(function(i, row) {
				var $row = jQuery(row);
				if (post === $row.data('post')) {
					$row.find('input[type=checkbox]').prop('checked', true);
					return false;
				}
			});
			postTableCheckboxesChanged();
		}
	}

	function selectPrevPostTableRow() {
		selectPostTableRow($el.find('tbody tr.selected:eq(0)').prev().data('post'));
	}

	function selectNextPostTableRow() {
		selectPostTableRow($el.find('tbody tr.selected:eq(0)').next().data('post'));
	}

	function showOrHidePostsTable() {
		if (getAllPosts().length === 0) {
			util.disableExitConfirmation();
			$el.find('#post-upload-step2').fadeOut();
		} else {
			util.enableExitConfirmation();
			$el.find('#post-upload-step2').fadeIn();
		}
	}

	function removeButtonClicked(e) {
		e.preventDefault();
		removePosts(getSelectedPosts());
	}

	function moveUpButtonClicked(e) {
		e.preventDefault();
		movePostsUp(getSelectedPosts());
	}

	function moveDownButtonClicked(e) {
		e.preventDefault();
		movePostsDown(getSelectedPosts());
	}

	function removePosts(posts) {
		_.each(posts, function(post) {
			post.$tableRow.remove();
		});
		setAllPostsFromTable();
		showOrHidePostsTable();
		postTableCheckboxesChanged();
	}

	function movePostsUp(posts) {
		_.each(posts, function(post) {
			var $row = post.$tableRow;
			$row.insertBefore($row.prev('tr:not(.selected)'));
		});
		setAllPostsFromTable();
	}

	function movePostsDown(posts) {
		_.each(posts, function(post) {
			var $row = post.$tableRow;
			$row.insertAfter($row.next('tr:not(.selected)'));
		});
		setAllPostsFromTable();
	}

	function uploadNextPost() {
		messagePresenter.hideMessages($messages);

		var posts = getAllPosts();
		if (posts.length === 0) {
			util.disableExitConfirmation();
			router.navigate('#/posts');
			return;
		}

		messagePresenter.showInfo($messages, 'Uploading in progress&hellip;');
		var post = posts[0];
		var $row = post.$tableRow;

		var formData = {};
		if (post.url) {
			formData.url = post.url;
		} else {
			formData.content = post.content;
			formData.contentFileName = post.fileName;
		}
		formData.source = post.source;
		formData.safety = post.safety;
		formData.anonymous = (post.anonymous | 0);
		formData.tags = post.tags.join(' ');

		if (post.tags.length === 0) {
			messagePresenter.hideMessages($messages);
			messagePresenter.showError($messages, 'No tags set.');
			interactionEnabled = true;
			return;
		}

		promise.wait(api.post('/posts', formData))
			.then(function(response) {
				$row.slideUp(function(response) {
					$row.remove();
					posts.shift();
					setAllPosts(posts);
					uploadNextPost();
				});
			}).fail(function(response) {
				messagePresenter.hideMessages($messages);
				messagePresenter.showError($messages, response.json && response.json.error || response);
				interactionEnabled = true;
			});
	}

	function submitButtonClicked(e) {
		e.preventDefault();
		if (!interactionEnabled) {
			return;
		}

		$el.find('tbody input[type=checkbox]').prop('checked', false);
		postTableCheckboxesChanged();

		interactionEnabled = false;
		uploadNextPost();

	}

	return {
		init: init,
		render: render,
	};

};

App.DI.register('postUploadPresenter', ['_', 'jQuery', 'keyboard', 'promise', 'util', 'auth', 'api', 'router', 'topNavigationPresenter', 'messagePresenter'], App.Presenters.PostUploadPresenter);
