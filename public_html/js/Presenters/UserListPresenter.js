var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.UserListPresenter = function(
	_,
	jQuery,
	util,
	promise,
	auth,
	pagerPresenter,
	topNavigationPresenter) {

	var $el = jQuery('#content');
	var templates = {};
	var params;

	function init(params, loaded) {
		topNavigationPresenter.select('users');
		topNavigationPresenter.changeTitle('Users');

		promise.wait(
				util.promiseTemplate('user-list'),
				util.promiseTemplate('user-list-item'))
			.then(function(listTemplate, listItemTemplate) {
				templates.list = listTemplate;
				templates.listItem = listItemTemplate;

				render();
				loaded();

				pagerPresenter.init({
						baseUri: '#/users',
						backendUri: '/users',
						$target: $el.find('.pagination-target'),
						updateCallback: function(data, clear) {
							renderUsers(data.entities, clear);
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
		loaded();
	}

	function deinit() {
		pagerPresenter.deinit();
	}

	function render() {
		$el.html(templates.list());
		$el.find('.order a').click(orderLinkClicked);
	}

	function updateActiveOrder(activeOrder) {
		$el.find('.order li a').removeClass('active');
		$el.find('.order [data-order="' + activeOrder + '"]').addClass('active');
	}

	function renderUsers(users, clear) {
		var $target = $el.find('.users');

		if (clear) {
			$target.empty();
		}

		_.each(users, function(user) {
			var $item = jQuery('<li>' + templates.listItem({
				user: user,
				formatRelativeTime: util.formatRelativeTime,
			}) + '</li>');
			util.loadImagesNicely($item.find('img'));
			$target.append($item);
		});
	}

	function orderLinkClicked(e) {
		e.preventDefault();
		var $orderLink = jQuery(this);
		var activeSearchOrder = $orderLink.attr('data-order');
		params.query.order = activeSearchOrder;
		pagerPresenter.setQuery(params.query);
	}

	return {
		init: init,
		reinit: reinit,
		deinit: deinit,
		render: render,
	};

};

App.DI.register('userListPresenter', ['_', 'jQuery', 'util', 'promise', 'auth', 'pagerPresenter', 'topNavigationPresenter'], App.Presenters.UserListPresenter);
