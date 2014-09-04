var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PagedCollectionPresenter = function(util, promise, api) {

	var searchOrder;
	var searchQuery;
	var pageNumber;
	var baseUri;
	var backendUri;
	var renderCallback;

	var template;
	var pageSize;
	var totalRecords;

	function init(args) {
		parseSearchArgs(args.searchArgs);
		baseUri = args.baseUri;
		backendUri = args.backendUri;
		renderCallback = args.renderCallback;

		promise.wait(util.promiseTemplate('pager')).then(function(html) {
			template = _.template(html);
			//renderCallback({entities: [], totalRecords: 0});

			changePage(pageNumber);
		});
	}

	function changePage(newPageNumber) {
		pageNumber = newPageNumber;

		promise.wait(
			api.get(backendUri, {
				order: searchOrder,
				query: searchQuery,
				page: pageNumber
			}))
			.then(function(response) {
				totalRecords = response.json.totalRecords;
				pageSize = response.json.pageSize;
				renderCallback({
					entities: response.json.data,
					totalRecords: response.json.totalRecords});
			}).fail(function(response) {
				console.log(Error(response.json && response.json.error || response));
			});
	}

	function render($target) {
		var totalPages = Math.ceil(totalRecords / pageSize);
		var pages = [1, totalPages];
		var pagesAroundCurrent = 2;
		for (var i = -pagesAroundCurrent; i <= pagesAroundCurrent; i ++)
			if (pageNumber + i >= 1 && pageNumber + i <= totalPages)
				pages.push(pageNumber + i);
		if (pageNumber - pagesAroundCurrent - 1 == 2)
			pages.push(2);
		if (pageNumber + pagesAroundCurrent + 1 == totalPages - 1)
			pages.push(totalPages - 1);

		pages = pages.sort(function(a, b) { return a - b; }).filter(function(item, pos) {
			return !pos || item != pages[pos - 1];
		});

		$target.html(template({
			pages: pages,
			pageNumber: pageNumber,
			link: getPageChangeLink,
		}));
	}

	function getSearchQueryChangeLink(newSearchQuery) {
		return util.compileComplexRouteArgs(baseUri, {
				page: 1,
				order: searchOrder,
				query: newSearchQuery,
			});
	}

	function getSearchOrderChangeLink(newSearchOrder) {
		return util.compileComplexRouteArgs(baseUri, {
				page: 1,
				order: newSearchOrder,
				query: searchQuery,
			});
	}

	function getPageChangeLink(newPageNumber) {
		return util.compileComplexRouteArgs(baseUri, {
				page: newPageNumber,
				order: searchOrder,
				query: searchQuery,
			});
	}

	function parseSearchArgs(searchArgs) {
		var args = util.parseComplexRouteArgs(searchArgs);
		pageNumber = parseInt(args.page) || 1;
		searchOrder = args.order;
		searchQuery = args.query;
	}

	return {
		init: init,
		render: render,
		changePage: changePage,
		getSearchQueryChangeLink: getSearchQueryChangeLink,
		getSearchOrderChangeLink: getSearchOrderChangeLink,
		getPageChangeLink: getPageChangeLink
	};

};

App.DI.register('pagedCollectionPresenter', App.Presenters.PagedCollectionPresenter);
