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
	var $searchInput;
	var templates = {};

	var params;

	function init(_params, loaded) {
		topNavigationPresenter.select('tags');
		topNavigationPresenter.changeTitle('Tags');
		params = _params;
		params.query = params.query || {};

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
						updateCallback: function($page, data) {
							renderTags($page, data.entities);
						},
					},
					function() {
						reinit(params, function() {});
					});
			}).fail(function() {
				console.log(arguments);
				loaded();
			});
	}

	function reinit(_params, loaded) {
		params = _params;
		params.query = params.query || {};
		params.query.order = params.query.order || 'name,asc';
		updateActiveOrder(params.query.order);

		pagerPresenter.reinit({query: params.query});

		keyboard.keyup('p', function() {
			$el.find('table a').eq(0).focus();
		});

		keyboard.keyup('q', function() {
			$searchInput.eq(0).focus().select();
		});

		loaded();
		softRender();
	}

	function deinit() {
		pagerPresenter.deinit();
	}

	function render() {
		$el.html(templates.list());
		$searchInput = $el.find('input[name=query]');
		$el.find('form').submit(searchFormSubmitted);
		App.Controls.AutoCompleteInput($searchInput);
		softRender();
	}

	function softRender() {
		$searchInput.val(params.query.query);
	}


	function searchFormSubmitted(e) {
		e.preventDefault();
		updateSearch();
	}

	function updateSearch() {
		$searchInput.blur();
		params.query.query = $searchInput.val().trim();
		params.query.page = 1;
		pagerPresenter.setQuery(params.query);
	}

	function updateActiveOrder(activeOrder) {
		$el.find('.order li a.active').removeClass('active');
		$el.find('.order [href*="' + activeOrder + '"]').addClass('active');
	}

	function renderTags($page, tags) {
		var $target = $page.find('tbody');
		_.each(tags, function(tag) {
			var $item = jQuery(templates.listItem({
				tag: tag,
				util: util,
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
