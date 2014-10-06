var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.TagListPresenter = function(
	_,
	jQuery,
	util,
	promise,
	pagerPresenter,
	topNavigationPresenter) {

	var $el = jQuery('#content');
	var templates = {};

	function init(args, loaded) {
		topNavigationPresenter.select('tags');
		topNavigationPresenter.changeTitle('Tags');

		promise.wait(
				util.promiseTemplate('tag-list'),
				util.promiseTemplate('tag-list-item'))
			.then(function(listTemplate, listItemTemplate) {
				templates.list = listTemplate;
				templates.listItem = listItemTemplate;

				render();
				loaded();

				pagerPresenter.init({
						baseUri: '#/tags',
						backendUri: '/tags',
						$target: $el.find('.pagination-target'),
						updateCallback: function(data, clear) {
							renderTags(data.entities, clear);
						},
					},
					function() {
						reinit(args, function() {});
					});
			});
	}

	function reinit(args, loaded) {
		loaded();

		var searchArgs = util.parseComplexRouteArgs(args.searchArgs);
		searchArgs.order = searchArgs.order || 'name,asc';
		searchArgs.page = parseInt(searchArgs.page) || 1;
		updateActiveOrder(searchArgs.order);

		pagerPresenter.reinit({
			page: searchArgs.page,
			searchParams: {
				order: searchArgs.order}});
	}

	function deinit() {
		pagerPresenter.deinit();
	}

	function render() {
		$el.html(templates.list());
	}

	function updateActiveOrder(activeOrder) {
		$el.find('.order li a').removeClass('active');
		$el.find('.order [href*="' + activeOrder + '"]').addClass('active');
	}

	function renderTags(tags, clear) {
		var $target = $el.find('.tags');

		if (clear) {
			$target.empty();
		}

		_.each(tags, function(tag) {
			var $item = jQuery(templates.listItem({
				tag: tag,
				formatRelativeTime: util.formatRelativeTime,
			}));
			$target.append($item);
		});
	}

	return {
		init: init,
		reinit: reinit,
		deinit: deinit,
		render: render,
	};

};

App.DI.register('tagListPresenter', ['_', 'jQuery', 'util', 'promise', 'pagerPresenter', 'topNavigationPresenter'], App.Presenters.TagListPresenter);
