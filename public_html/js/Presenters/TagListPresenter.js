var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.TagListPresenter = function(
	_,
	jQuery,
	util,
	promise,
	keyboard,
	pagerPresenter,
	topNavigationPresenter) {

	var $el = jQuery('#content');
	var templates = {};

	function init(params, loaded) {
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
						reinit(params, function() {});
					});
			});
	}

	function reinit(params, loaded) {
		params.query = params.query || {};
		params.query.order = params.query.order || 'name,asc';
		updateActiveOrder(params.query.order);

		pagerPresenter.reinit({query: params.query});

		keyboard.keyup('p', function() {
			$el.find('table a').eq(0).focus();
		});

		loaded();
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

App.DI.register('tagListPresenter', ['_', 'jQuery', 'util', 'promise', 'keyboard', 'pagerPresenter', 'topNavigationPresenter'], App.Presenters.TagListPresenter);
