var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.HomePresenter = function(
	_,
	jQuery,
	util,
	promise,
	api,
	topNavigationPresenter,
	messagePresenter) {

	var $el = jQuery('#content');
    var homeTemplate;
    var postContentTemplate;
    var post;

	function init(args, loaded) {
		topNavigationPresenter.select('home');
		topNavigationPresenter.changeTitle('Home');

		promise.waitAll(
				util.promiseTemplate('home'),
				util.promiseTemplate('post-content'),
				api.get('/posts/featured'))
			.then(function(
					homeTemplateHtml,
					postContentTemplateHtml,
					response) {
				homeTemplate = _.template(homeTemplateHtml);
				postContentTemplate = _.template(postContentTemplateHtml);

				post = response.json;
				render();
				loaded();

			}).fail(function(response) {
				messagePresenter.showError($el, response.json && response.json.error || response);
			});
	}

	function render() {
		$el.html(homeTemplate({
			post: post,
			postContentTemplate: postContentTemplate,
			title: topNavigationPresenter.getBaseTitle(),
			formatRelativeTime: util.formatRelativeTime,
		}));
	}

	return {
		init: init,
		render: render,
	};

};

App.DI.register('homePresenter', ['_', 'jQuery', 'util', 'promise', 'api', 'topNavigationPresenter', 'messagePresenter'], App.Presenters.HomePresenter);
