var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.UserListPresenter = function(
	jQuery,
	util,
	router,
	pagedCollectionPresenter,
	topNavigationPresenter) {

	var $el = jQuery('#content');
	var template;
	var userList = [];
	var activeSearchOrder;

	function init(args) {
		topNavigationPresenter.select('users');
		activeSearchOrder = util.parseComplexRouteArgs(args.searchArgs).order;

		util.loadTemplate('user-list').then(function(html) {
			template = _.template(html);

			pagedCollectionPresenter.init({
				searchArgs: args.searchArgs,
				baseUri: '#/users',
				backendUri: '/users',
				renderCallback: function updateCollection(data) {
					userList = data.entities;
					render();
				}});
		});
	}

	function render() {
		$el.html(template({
			userList: userList,
		}));
		$el.find('.order a').click(orderLinkClicked);
		$el.find('.order [data-order="' + activeSearchOrder + '"]').parent('li').addClass('active');
		console.log(activeSearchOrder);

		var $pager = $el.find('.pager');
		pagedCollectionPresenter.render($pager);
	};

	function orderLinkClicked(e)
	{
		e.preventDefault();
		var $orderLink = jQuery(this);
		activeSearchOrder = $orderLink.attr('data-order');
		router.navigate(pagedCollectionPresenter.getSearchOrderChangeLink(activeSearchOrder));
	}

	return {
		init: init,
		render: render
	};

};

App.DI.register('userListPresenter', App.Presenters.UserListPresenter);
