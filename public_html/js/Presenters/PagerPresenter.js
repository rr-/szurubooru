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
	var template;
	var forceClear = false;

	var baseUri;
	var updateCallback;

	function init(args, loaded) {
		baseUri = args.baseUri;
		updateCallback = args.updateCallback;

		messagePresenter.instant = true;

		$target = args.$target;
		targetContent = jQuery(args.$target).html();

		pager.init({url: args.backendUri});
		pager.setSearchParams(args.searchParams);

		promise.wait(util.promiseTemplate('pager'))
			.then(function(html) {
				template = _.template(html);
				render();
				loaded();
			});
	}

	function reinit(args, loaded) {
		forceClear = !_.isEqual(args.searchParams, pager.getSearchParams()) || parseInt(args.page) !== pager.getPage();

		pager.setSearchParams(args.searchParams);
		pager.setPage(args.page || 1);

		retrieve()
			.then(loaded)
			.fail(loaded);

		if (!endlessScroll) {
			keyboard.keydown('a', prevPage);
			keyboard.keydown('d', nextPage);
		}
	}

	function prevPage() {
		pager.prevPage();
		syncUrl();
	}

	function nextPage() {
		pager.nextPage();
		syncUrl();
	}

	function nextPageInplace() {
		pager.nextPage();
		syncUrlInplace();
	}

	function setPage(newPage) {
		pager.setPage(newPage);
		syncUrl();
	}

	function setSearchParams(newSearchParams) {
		clearContent();
		pager.setSearchParams(newSearchParams);
		syncUrl();
	}

	function getUrl() {
		return util.compileComplexRouteArgs(
			baseUri,
			_.extend(
				{},
				pager.getSearchParams(),
				{page: pager.getPage()}));
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
			pager.retrieve()
				.then(function(response) {
					showPageList();
					updateCallback(response, forceClear || !endlessScroll);
					forceClear = false;

					refreshPageList();
					hideSpinner();
					attachNextPageLoader();
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
				nextPageInplace();
				window.clearInterval(scrollInterval);
			}
		}, 100);
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
				setPage(page);
			});
			$a.addClass('big-button');
			$a.attr('href', '#');
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
		$target.html(template({originalHtml: targetContent}));
		$messages = $target.find('.pagination-content');
		$pageList = $target.find('.page-list');
		if (endlessScroll) {
			$pageList.remove();
		} else {
			refreshPageList();
		}
	}

	return {
		init: init,
		reinit: reinit,
		setPage: setPage,
		setSearchParams: setSearchParams,
	};

};

App.DI.register('pagerPresenter', ['_', 'jQuery', 'util', 'promise', 'api', 'keyboard', 'router', 'pager', 'presenterManager', 'messagePresenter', 'browsingSettings'], App.Presenters.PagerPresenter);
