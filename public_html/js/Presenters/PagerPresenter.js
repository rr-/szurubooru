var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PagerPresenter = function(
	_,
	jQuery,
	util,
	promise,
	api,
	keyboard,
	router,
	pager,
	presenterManager,
	messagePresenter,
	browsingSettings) {

	var $target;
	var $pageList;
	var $messages;
	var targetContent;
	var endlessScroll = browsingSettings.getSettings().endlessScroll;
	var scrollInterval;
	var templates = {};
	var forceClear = false;

	var baseUri;
	var updateCallback;

	function init(params, loaded) {
		baseUri = params.baseUri;
		updateCallback = params.updateCallback;

		messagePresenter.instant = true;

		$target = params.$target;
		targetContent = jQuery(params.$target).html();

		if (forceClear) {
			clearContent();
		}
		pager.init({url: params.backendUri});
		setQuery(params.query);

		promise.wait(util.promiseTemplate('pager'))
			.then(function(template) {
				templates.pager = template;
				render();
				loaded();
			});
	}

	function reinit(params, loaded) {
		if (forceClear) {
			clearContent();
		}
		setQuery(params.query);

		promise.wait(retrieve())
			.then(loaded)
			.fail(loaded);

		if (!endlessScroll) {
			keyboard.keydown('a', function() { pager.prevPage(); syncUrl(); });
			keyboard.keydown('d', function() { pager.nextPage(); syncUrl(); });
		}
	}

	function deinit() {
		detachNextPageLoader();
	}

	function getUrl() {
		return util.appendComplexRouteParam(baseUri, _.extend({}, pager.getSearchParams(), {page: pager.getPage()}));
	}

	function syncUrl() {
		router.navigate(getUrl());
	}

	function syncUrlInplace() {
		router.navigateInplace(getUrl());
	}

	function showSpinner() {
		if (endlessScroll) {
			$target.find('.spinner').show();
		} else {
			presenterManager.showContentSpinner();
		}
	}

	function hideSpinner() {
		if (endlessScroll) {
			$target.find('.spinner').hide();
		} else {
			presenterManager.hideContentSpinner();
		}
	}

	function retrieve() {
		messagePresenter.hideMessages($messages);
		showSpinner();

		return promise.make(function(resolve, reject) {
			promise.wait(pager.retrieve())
				.then(function(response) {
					updateCallback(response, forceClear || !endlessScroll);
					forceClear = false;
					if (!response.entities.length) {
						messagePresenter.showInfo($messages, 'No data to show');
						if (pager.getVisiblePages().length === 1) {
							hidePageList();
						} else  {
							showPageList();
						}
					} else {
						showPageList();
					}

					if (pager.getPage() < response.totalPages) {
						attachNextPageLoader();
					}
					refreshPageList();
					hideSpinner();
					resolve();
				}).fail(function(response) {
					clearContent();
					hidePageList();
					hideSpinner();
					messagePresenter.showError($messages, response.json && response.json.error || response);

					reject();
				});
		});
	}

	function clearContent() {
		updateCallback({entities: [], totalRecords: 0}, true);
	}

	function attachNextPageLoader() {
		if (!endlessScroll) {
			return;
		}

		window.clearInterval(scrollInterval);
		scrollInterval = window.setInterval(function() {
			var baseLine = $target.offset().top + $target.innerHeight();
			var scrollY = jQuery(window).scrollTop() + jQuery(window).height();
			if (scrollY > baseLine) {
				pager.nextPage();
				syncUrlInplace();
				window.clearInterval(scrollInterval);
			}
		}, 100);
	}

	function detachNextPageLoader() {
		window.clearInterval(scrollInterval);
	}

	function showPageList() {
		$pageList.show();
	}

	function hidePageList() {
		$pageList.hide();
	}

	function refreshPageList() {
		var pages = pager.getVisiblePages();
		$pageList.empty();
		var lastPage = 0;
		_.each(pages, function(page) {
			if (page - lastPage > 1) {
				$pageList.append(jQuery('<li><a>&hellip;</a></li>'));
			}
			lastPage = page;

			var $a = jQuery('<a/>');
			$a.click(function() {
				pager.setPage(page);
				syncUrl();
			});
			$a.addClass('big-button');
			$a.text(page);
			if (page === pager.getPage()) {
				$a.addClass('active');
			}
			var $li = jQuery('<li/>');
			$li.append($a);
			$pageList.append($li);
		});
	}

	function render() {
		$target.html(templates.pager({originalHtml: targetContent}));
		$messages = $target.find('.pagination-content');
		$pageList = $target.find('.page-list');
		if (endlessScroll) {
			$pageList.remove();
		} else {
			refreshPageList();
		}
	}

	function setQuery(query) {
		if (!query) {
			return;
		}
		query.page = parseInt(query.page) || 1;
		var page = query.page;
		delete query.page;
		forceClear =
			query.query !== pager.getSearchParams().query ||
			query.order !== pager.getSearchParams().order ||
			parseInt(page) !== pager.getPage();
		pager.setSearchParams(query);
		pager.setPage(page);
	}

	function setQueryAndSyncUrl(query) {
		setQuery(query);
		syncUrl();
	}

	return {
		init: init,
		reinit: reinit,
		deinit: deinit,
		syncUrl: syncUrl,
		setQuery: setQueryAndSyncUrl,
	};

};

App.DI.register('pagerPresenter', ['_', 'jQuery', 'util', 'promise', 'api', 'keyboard', 'router', 'pager', 'presenterManager', 'messagePresenter', 'browsingSettings'], App.Presenters.PagerPresenter);
