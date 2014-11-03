var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.TagPresenter = function(
	_,
	jQuery,
	util,
	promise,
	auth,
	api,
	tagList,
	router,
	keyboard,
	topNavigationPresenter,
	messagePresenter) {

	var $el = jQuery('#content');
	var $messages = $el;
	var templates = {};
	var implicationsTagInput;
	var suggestionsTagInput;

	var tag;
	var posts;
	var siblings;

	var privileges = {};

	function init(params, loaded) {
		topNavigationPresenter.select('tags');
		topNavigationPresenter.changeTitle('Tags');

		privileges.canChangeName = auth.hasPrivilege(auth.privileges.changeTagName);
		privileges.canChangeCategory = auth.hasPrivilege(auth.privileges.changeTagCategory);
		privileges.canChangeImplications = auth.hasPrivilege(auth.privileges.changeTagImplications);
		privileges.canChangeSuggestions = auth.hasPrivilege(auth.privileges.changeTagSuggestions);
		privileges.canBan = auth.hasPrivilege(auth.privileges.banTags);

		promise.wait(
				util.promiseTemplate('tag'),
				util.promiseTemplate('post-list-item'))
			.then(function(tagTemplate, postListItemTemplate) {
				templates.tag = tagTemplate;
				templates.postListItem = postListItemTemplate;

				reinit(params, loaded);
			}).fail(function() {
				console.log(arguments);
				loaded();
			});
	}

	function reinit(params, loaded) {
		var tagName = params.tagName;

		messagePresenter.hideMessages($messages);

		promise.wait(
				api.get('tags/' + tagName),
				api.get('tags/' + tagName + '/siblings'),
				api.get('posts', {query: tagName}))
			.then(function(tagResponse, siblingsResponse, postsResponse) {
				tag = tagResponse.json;
				siblings = siblingsResponse.json.data;
				posts = postsResponse.json.data;
				posts = posts.slice(0, 8);

				render();
				loaded();

				renderPosts(posts);
			}).fail(function(tagResponse, siblingsResponse, postsResponse) {
				messagePresenter.showError($messages, tagResponse.json.error || siblingsResponse.json.error || postsResponse.json.error);
				loaded();
			});
	}

	function render() {
		$el.html(templates.tag({
			privileges: privileges,
			tag: tag,
			siblings: siblings,
			tagCategories: JSON.parse(jQuery('head').attr('data-tag-categories')),
		}));
		$el.find('.post-list').hide();
		$el.find('form').submit(editFormSubmitted);
		implicationsTagInput = App.Controls.TagInput($el.find('[name=implications]'));
		suggestionsTagInput = App.Controls.TagInput($el.find('[name=suggestions]'));
	}

	function editFormSubmitted(e) {
		e.preventDefault();
		var $form = $el.find('form');
		var formData = {};

		if (privileges.canChangeName) {
			formData.name = $form.find('[name=name]').val();
		}

		if (privileges.canChangeCategory) {
			formData.category = $form.find('[name=category]:checked').val();
		}

		if (privileges.canBan) {
			formData.banned = $form.find('[name=ban]').is(':checked') ? 1 : 0;
		}

		if (privileges.canChangeImplications) {
			formData.implications = implicationsTagInput.getTags().join(' ');
		}

		if (privileges.canChangeSuggestions) {
			formData.suggestions = suggestionsTagInput.getTags().join(' ');
		}

		promise.wait(api.put('/tags/' + tag.name, formData))
			.then(function(response) {
				tag = response.json;
				render();
				renderPosts(posts);
				tagList.refreshTags();
				router.navigateInplace('#/tag/' + tag.name);
			}).fail(function(response) {
				window.alert(response.json && response.json.error || 'An error occured.');
			});
	}

	function renderPosts(posts) {
		var $target = $el.find('.post-list ul');
		_.each(posts, function(post) {
			var $post = jQuery('<li>' + templates.postListItem({
				util: util,
				post: post,
				query: {query: tag.name},
			}) + '</li>');
			$target.append($post);
		});
		if (posts.length > 0) {
			$el.find('.post-list').fadeIn();
			keyboard.keyup('p', function() {
				$el.find('.post-list a').eq(0).focus();
			});
		}
	}

	return {
		init: init,
		reinit: reinit,
	};

};

App.DI.register('tagPresenter', ['_', 'jQuery', 'util', 'promise', 'auth', 'api', 'tagList', 'router', 'keyboard', 'topNavigationPresenter', 'messagePresenter'], App.Presenters.TagPresenter);
