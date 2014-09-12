var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PagedCollectionPresenter = function(
	_,
	jQuery,
	util,
	promise,
	api,
	mousetrap,
	router,
	browsingSettings) {

	var endlessScroll = browsingSettings.getSettings().endlessScroll;
	var scrollInterval;
	var template;
	var totalPages;
	var forceClear;

	var pageNumber;
	var searchParams;
	var baseUri;
	var backendUri;
	var updateCallback;
	var failCallback;

	function init(args) {
		forceClear = !_.isEqual(args.searchParams, searchParams) || parseInt(args.page) !== pageNumber + 1;
		searchParams = args.searchParams;
		pageNumber = parseInt(args.page) || 1;

		baseUri = args.baseUri;
		backendUri = args.backendUri;
		updateCallback = args.updateCallback;
		failCallback = args.failCallback;

		promise.wait(util.promiseTemplate('pager')).then(function(html) {
			template = _.template(html);
			softChangePage(pageNumber);

			if (!endlessScroll) {
				mousetrap.bind('a', function(e) {
					if (!e.altKey && !e.ctrlKey) {
						prevPage();
					}
				});
				mousetrap.bind('d', function(e) {
					if (!e.altKey && !e.ctrlKey) {
						nextPage();
					}
				});
			}
		});
	}

	function prevPage() {
		if (pageNumber > 1) {
			changePage(pageNumber - 1);
		}
	}

	function nextPage() {
		if (pageNumber < totalPages) {
			changePage(pageNumber + 1);
		}
	}

	function nextPageInplace() {
		if (pageNumber < totalPages) {
			changePageInplace(pageNumber + 1);
		}
	}

	function changePageInplace(newPageNumber) {
		router.navigateInplace(getPageChangeLink(newPageNumber));
	}

	function changePage(newPageNumber) {
		router.navigate(getPageChangeLink(newPageNumber));
	}

	function softChangePage(newPageNumber) {
		pageNumber = newPageNumber;

		promise.wait(
			api.get(backendUri, _.extend({}, searchParams, {page: pageNumber})))
			.then(function(response) {
				var pageSize = response.json.pageSize;
				var totalRecords = response.json.totalRecords;
				totalPages = Math.ceil(totalRecords / pageSize);

				var $target = updateCallback({
					entities: response.json.data,
					totalRecords: totalRecords},
					forceClear || !endlessScroll);
				forceClear = false;

				render($target);
			}).fail(function(response) {
				if (typeof(failCallback) !== 'undefined') {
					failCallback(response);
				} else {
					console.log(new Error(response.json && response.json.error || response));
				}
			});
	}

	function render($target) {
		var pages = getVisiblePages();

		if (!endlessScroll) {
			$target.find('.pager').remove();
			$target.append(template({
				pages: pages,
				pageNumber: pageNumber,
				link: getPageChangeLink,
			}));
		} else {
			var $scroller = jQuery('<div/>');
			window.clearInterval(scrollInterval);
			scrollInterval = window.setInterval(function() {
				if ($scroller.is(':visible')) {
					nextPageInplace();
					window.clearInterval(scrollInterval);
				}
			}, 50);
			$target.append($scroller);
		}
	}

	function getVisiblePages() {
		var pages = [1, totalPages];
		var pagesAroundCurrent = 2;
		for (var i = -pagesAroundCurrent; i <= pagesAroundCurrent; i ++) {
			if (pageNumber + i >= 1 && pageNumber + i <= totalPages) {
				pages.push(pageNumber + i);
			}
		}
		if (pageNumber - pagesAroundCurrent - 1 === 2) {
			pages.push(2);
		}
		if (pageNumber + pagesAroundCurrent + 1 === totalPages - 1) {
			pages.push(totalPages - 1);
		}

		pages = pages.sort(function(a, b) { return a - b; }).filter(function(item, pos) {
			return !pos || item !== pages[pos - 1];
		});

		return pages;
	}

	function getSearchChangeLink(newSearchParams) {
		return util.compileComplexRouteArgs(baseUri, _.extend({}, searchParams, newSearchParams, {page: 1}));
	}

	function getPageChangeLink(newPageNumber) {
		return util.compileComplexRouteArgs(baseUri, _.extend({}, searchParams, {page: newPageNumber}));
	}

	return {
		init: init,
		render: render,
		changePage: changePage,
		getSearchChangeLink: getSearchChangeLink,
		getPageChangeLink: getPageChangeLink
	};

};

App.DI.register('pagedCollectionPresenter', ['_', 'jQuery', 'util', 'promise', 'api', 'mousetrap', 'router', 'browsingSettings'], App.Presenters.PagedCollectionPresenter);
