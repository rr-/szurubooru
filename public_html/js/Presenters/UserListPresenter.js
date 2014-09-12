var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.UserListPresenter = function(
	_,
	jQuery,
	util,
	promise,
	auth,
	router,
	pagedCollectionPresenter,
	topNavigationPresenter,
	messagePresenter) {

	var $el = jQuery('#content');
	var listTemplate;
	var itemTemplate;

	function init(args) {
		topNavigationPresenter.select('users');
		topNavigationPresenter.changeTitle('Users');

		promise.waitAll(
			util.promiseTemplate('user-list'),
			util.promiseTemplate('user-list-item')).then(function(listHtml, itemHtml) {
			listTemplate = _.template(listHtml);
			itemTemplate = _.template(itemHtml);

			render();
			reinit(args);
		});
	}

	function reinit(args) {
		var searchArgs = util.parseComplexRouteArgs(args.searchArgs);
		searchArgs.order = searchArgs.order || 'name';

		updateActiveOrder(searchArgs.order);
		initPaginator(searchArgs);
	}

	function initPaginator(searchArgs) {
		pagedCollectionPresenter.init({
			page: searchArgs.page,
			searchParams: {order: searchArgs.order},
			baseUri: '#/users',
			backendUri: '/users',
			updateCallback: function(data, clear) {
				renderUsers(data.entities, clear);
				return $el.find('.pagination-content');
			},
			failCallback: function(response) {
				$el.empty();
				messagePresenter.showError($el, response.json && response.json.error || response);
			}});
	}

	function render() {
		$el.html(listTemplate());
		$el.find('.order a').click(orderLinkClicked);
	}

	function updateActiveOrder(activeOrder) {
		$el.find('.order li').removeClass('active');
		$el.find('.order [data-order="' + activeOrder + '"]').parent('li').addClass('active');
	}

	function renderUsers(users, clear) {
		var $target = $el.find('.users');

		var all = '';
		_.each(users, function(user) {
			all += itemTemplate({
				user: user,
				formatRelativeTime: util.formatRelativeTime,
			});
		});

		if (clear) {
			$target.html(all);
		} else {
			$target.append(all);
		}
	}

	function orderLinkClicked(e) {
		e.preventDefault();
		var $orderLink = jQuery(this);
		var activeSearchOrder = $orderLink.attr('data-order');
		router.navigate(pagedCollectionPresenter.getSearchChangeLink({order: activeSearchOrder}));
	}

	return {
		init: init,
		reinit: reinit,
		render: render,
	};

};

App.DI.register('userListPresenter', ['_', 'jQuery', 'util', 'promise', 'auth', 'router', 'pagedCollectionPresenter', 'topNavigationPresenter', 'messagePresenter'], App.Presenters.UserListPresenter);
