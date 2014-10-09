var App = App || {};
App.Services = App.Services || {};

App.Services.PostsAroundCalculator = function(_, promise, util, pager) {

	pager.init({url: '/posts'});

	function resetCache() {
		pager.resetCache();
	}

	function getLinksToPostsAround(query, postId) {
		return promise.make(function(resolve, reject) {
			pager.setSearchParams(query);
			pager.setPage(query.page);
			promise.wait(pager.retrieveCached())
				.then(function(response) {
					var postIds = _.pluck(response.entities, 'id');
					var position = _.indexOf(postIds, postId);

					if (position === -1) {
						resolve(null, null);
					}

					promise.wait(
							getLinkToPostAround(postIds, position, query.page, -1),
							getLinkToPostAround(postIds, position, query.page, 1))
						.then(function(nextPostUrl, prevPostUrl) {
							resolve(nextPostUrl, prevPostUrl);
						});
				});
		});
	}

	function getLinkToPostAround(postIds, position, page, direction) {
		return promise.make(function(resolve, reject) {
			if (position + direction >= 0 && position + direction < postIds.length) {
				var url = util.appendComplexRouteParam(
					'#/post/' + postIds[position + direction],
					_.extend({page: page}, pager.getSearchParams()));
				resolve(url);
			} else if (page + direction >= 1) {
				pager.setPage(page + direction);
				promise.wait(pager.retrieveCached()).then(function(response) {
					if (response.entities.length) {
						var post = direction === - 1 ?
							_.last(response.entities) :
							_.first(response.entities);

						var url = util.appendComplexRouteParam(
							'#/post/' + post.id,
							_.extend({page: page + direction}, pager.getSearchParams()));
						resolve(url);
					} else {
						resolve(null);
					}
				});
			} else {
				resolve(null);
			}
		});
	}

	return {
		resetCache: resetCache,
		getLinksToPostsAround: getLinksToPostsAround,
	};
};

App.DI.register('postsAroundCalculator', ['_', 'promise', 'util', 'pager'], App.Services.PostsAroundCalculator);
