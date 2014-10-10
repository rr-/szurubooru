var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.TagPresenter = function(
	_,
	jQuery,
	util,
	promise,
	auth,
	api,
	router,
	keyboard,
	topNavigationPresenter,
	messagePresenter) {

	var $el = jQuery('#content');
	var $messages = $el;
	var templates = {};

	var tag;
	var tagName;

	var privileges = {};

	function init(params, loaded) {
		topNavigationPresenter.select('tags');
		topNavigationPresenter.changeTitle('Tags');

		privileges.canChangeName = auth.hasPrivilege(auth.privileges.changeTagName);

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
		tagName = params.tagName;

		messagePresenter.hideMessages($messages);

		promise.wait(
				api.get('tags/' + tagName),
				api.get('posts', {query: tagName}))
			.then(function(tagResponse, postsResponse) {
				tag = tagResponse.json;
				var posts = postsResponse.json.data;
				posts = posts.slice(0, 8);

				render();
				loaded();

				renderPosts(posts);
			}).fail(function(tagResponse, postsResponse) {
				messagePresenter.showError($messages, tagResponse.json.error || postsResponse.json.error);
				loaded();
			});
	}

	function render() {
		$el.html(templates.tag({privileges: privileges, tag: tag, tagName: tagName}));
		$el.find('.post-list').hide();
		$el.find('form').submit(editFormSubmitted);
	}

	function editFormSubmitted(e) {
		e.preventDefault();
		var $form = $el.find('form');
		var formData = {};

		if (privileges.canChangeName) {
			formData.name = $form.find('[name=name]').val();
		}

		promise.wait(api.put('/tags/' + tag.name, formData))
			.then(function(response) {
				tag = response.json;
				render();
				router.navigate('#/tag/' + tag.name);
			}).fail(function(response) {
				console.log(response);
				window.alert('An error occurred');
			});
	}

	function renderPosts(posts) {
		var $target = $el.find('.post-list ul');
		_.each(posts, function(post) {
			var $post = jQuery('<li>' + templates.postListItem({
				util: util,
				post: post,
				query: {query: tagName},
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

App.DI.register('tagPresenter', ['_', 'jQuery', 'util', 'promise', 'auth', 'api', 'router', 'keyboard', 'topNavigationPresenter', 'messagePresenter'], App.Presenters.TagPresenter);
