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

	function init(args, loaded) {
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
			$target.append(jQuery('<li>' + templates.listItem({
				user: user,
				formatRelativeTime: util.formatRelativeTime,
			}) + '</li>'));
		});
	}

	function orderLinkClicked(e) {
		e.preventDefault();
		var $orderLink = jQuery(this);
		var activeSearchOrder = $orderLink.attr('data-order');
		pagerPresenter.setSearchParams({order: activeSearchOrder});
	}

	return {
		init: init,
		reinit: reinit,
		deinit: deinit,
		render: render,
	};

};

App.DI.register('userListPresenter', ['_', 'jQuery', 'util', 'promise', 'auth', 'pagerPresenter', 'topNavigationPresenter'], App.Presenters.UserListPresenter);
