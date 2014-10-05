var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PostCommentListPresenter = function(
	_,
	jQuery,
	util,
	promise,
	api,
	auth,
	topNavigationPresenter,
	messagePresenter) {

	var $el;
	var privileges;
	var templates = {};

	var post;
	var comments = [];

	function init(args, loaded) {
		$el = args.$target;
		post = args.post;
		comments = args.comments || [];

		privileges = {
			canListComments: auth.hasPrivilege(auth.privileges.listComments),
			canAddComments: auth.hasPrivilege(auth.privileges.addComments),
			editOwnComments: auth.hasPrivilege(auth.privileges.editOwnComments),
			editAllComments: auth.hasPrivilege(auth.privileges.editAllComments),
			deleteOwnComments: auth.hasPrivilege(auth.privileges.deleteOwnComments),
			deleteAllComments: auth.hasPrivilege(auth.privileges.deleteAllComments),
		};

		promise.wait(
				util.promiseTemplate('post-comment-list'),
				util.promiseTemplate('comment-list-item'),
				util.promiseTemplate('comment-form'),
				comments.length === 0 ? api.get('/comments/' + args.post.id) : null)
			.then(function(
					commentListTemplate,
					commentListItemTemplate,
					commentFormTemplate,
					commentsResponse)
				{
					templates.commentList = commentListTemplate;
					templates.commentListItem = commentListItemTemplate;
					templates.commentForm = commentFormTemplate;
					if (commentsResponse) {
						comments = commentsResponse.json.data;
					}

					render();
					loaded();
				})
			.fail(function() { console.log(new Error(arguments)); });
	}

	function render() {
		$el.html(templates.commentList(
			_.extend(
				{
					commentListItemTemplate: templates.commentListItem,
					commentFormTemplate: templates.commentForm,
					formatRelativeTime: util.formatRelativeTime,
					formatMarkdown: util.formatMarkdown,
					comments: comments,
					post: post,
				},
				privileges)));

		$el.find('.comment-add form button[type=submit]').click(function(e) { commentFormSubmitted(e, null); });
		renderComments(comments, true);
	}

	function renderComments(comments, clear) {
		var $target = $el.find('.comments');
		var $targetList = $el.find('ul');

		if (comments.length > 0) {
			$target.show();
		} else {
			$target.hide();
		}

		if (clear) {
			$targetList.empty();
		}

		_.each(comments, function(comment) {
			renderComment($targetList, comment);
		});
	}

	function renderComment($targetList, comment) {
		var $item = jQuery('<li>' + templates.commentListItem({
			comment: comment,
			formatRelativeTime: util.formatRelativeTime,
			formatMarkdown: util.formatMarkdown,
			canEditComment: auth.isLoggedIn(comment.user.name) ? privileges.editOwnComments : privileges.editAllComments,
			canDeleteComment: auth.isLoggedIn(comment.user.name) ? privileges.deleteOwnComments : privileges.deleteAllComments,
		}) + '</li>');
		$targetList.append($item);
		$item.find('a.edit').click(function(e) {
			e.preventDefault();
			editCommentStart($item, comment);
		});
		$item.find('a.delete').click(function(e) {
			e.preventDefault();
			deleteComment($item, comment);
		});
	}

	function commentFormSubmitted(e, comment) {
		e.preventDefault();
		var $button = jQuery(e.target);
		var $form = $button.parents('form');
		var sender = $button.val();
		if (sender === 'preview') {
			previewComment($form);
		} else {
			submitComment($form, comment);
		}
	}

	function previewComment($form) {
		var $preview = $form.find('.preview');
		$preview.slideUp('fast', function() {
			$preview.html(util.formatMarkdown($form.find('textarea').val()));
			$preview.slideDown('fast');
		});
	}

	function submitComment($form, commentToEdit) {
		$form.find('.preview').slideUp();
		var $textarea = $form.find('textarea');

		var data = {text: $textarea.val()};
		var p;
		if (commentToEdit) {
			p = promise.wait(api.put('/comments/' + commentToEdit.id, data));
		} else {
			p = promise.wait(api.post('/comments/' + post.id, data));
		}

		p.then(function(response) {
			$textarea.val('');
			var comment = response.json;

			if (commentToEdit) {
				$form.slideUp(function() {
					$form.remove();
				});
				comments = _.map(comments, function(c) { return c.id === commentToEdit.id ? comment : c; });
			} else {
				comments.push(comment);
			}
			render();
		}).fail(function(response) {
			window.alert(response.json && response.json.error || response);
		});
	}

	function editCommentStart($item, comment) {
		if ($item.find('.comment-form').length > 0) {
			return;
		}
		var $form = jQuery(templates.commentForm({title: 'Edit comment', text: comment.text}));
		$item.find('.body').append($form);
		$item.find('form button[type=submit]').click(function(e) { commentFormSubmitted(e, comment); });
	}

	function deleteComment($item, comment) {
		if (!window.confirm('Are you sure you want to delete this comment?')) {
			return;
		}
		promise.wait(api.delete('/comments/' + comment.id))
			.then(function(response) {
				comments = _.filter(comments, function(c) { return c.id !== comment.id; });
				renderComments(comments, true);
			}).fail(function(response) {
				window.alert(response.json && response.json.error || response);
			});
	}

	return {
		init: init,
		render: render,
	};
};

App.DI.register('postCommentListPresenter', ['_', 'jQuery', 'util', 'promise', 'api', 'auth', 'topNavigationPresenter', 'messagePresenter'], App.Presenters.PostCommentListPresenter);
