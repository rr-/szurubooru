'use strict';

const api = require('../api.js');
const router = require('../router.js');
const views = require('../util/views.js');
const topNavigation = require('../models/top_navigation.js');
const Post = require('../models/post.js');
const PostMetric = require('../models/post_metric.js');
const PostMetricRange = require('../models/post_metric_range.js');
const PostList = require('../models/post_list.js');
const MetricSorterView = require('../views/metric_sorter_view.js');
const EmptyView = require('../views/empty_view.js');

const LEFT = 'left';
const RIGHT = 'right';

class MetricSorterController  {
    constructor(ctx) {
        if (!api.hasPrivilege('posts:view') ||
            !api.hasPrivilege('metrics:edit:posts')) {
            this._view = new EmptyView();
            this._view.showError('You don\'t have privileges to edit post metric values.');
            return;
        }

        topNavigation.activate('posts');
        topNavigation.setTitle('Sorting metrics');

        this._ctx = ctx;
        this._metricNames = (ctx.parameters.metrics || '')
            .split(' ')
            .filter(m => m);
        if (!this._metricNames.length) {
            this._view = new EmptyView();
            this._view.showError('No metrics selected');
            return;
        }
        this._primaryMetricName = this._metricNames[0];

        this._view = new MetricSorterView({
            primaryMetric: this._primaryMetricName,
            greaterPost: RIGHT,
        });
        this._view.addEventListener('submit', e => this._evtSubmit(e));
        this._view.addEventListener('skip', e => this._evtSkip(e));
        this._view.addEventListener('changeMetric', e => this._evtChangeMetric(e));

        if (ctx.parameters.id === 'random') {
            this.startSortingRandomPost();
        } else {
            this.startSortingPost(ctx.parameters.id);
        }
    }

    startSortingPost(id) {
        this._view.clearMessages();
        this._foundExactValue = false;
        Post.get(id).then(post => {
            this._unsortedPost = post;
            this._view.installLeftPost(post);
            this.reloadMedianPost();
        }).catch(error => {
            this._view.showError(error.message)
        });
    }

    startSortingRandomPost() {
        this._view.clearMessages();
        this._getRandomUnsortedPostId().then(id => {
            this._ctx.parameters.id = id;
            router.replace(views.getMetricSorterUrl(id, this._ctx.parameters));
            this.startSortingPost(id);
        }).catch(error => {
            this._view.showError(error.message)
        });
    }

    reloadMedianPost() {
        const metricName = this._primaryMetricName;
        let range = this._getOrCreateRange(this._unsortedPost, metricName);
        this._tryGetMedianPost(metricName, range).then(medianResponse => {
            if (medianResponse.post) {
                this._sortedPost = medianResponse.post;
                this._view.installRightPost(this._sortedPost);
            } else {
                // No existing metrics, apply the median value
                this._foundExactValue = true;
                let exactValue = (medianResponse.range.low + medianResponse.range.high) / 2;
                this._view.showSuccess(`Found exact value: ${exactValue}`);
                this._setExactMetric(this._unsortedPost, metricName, exactValue);
                //TODO: maybe allow to set exact value?
            }
        }).catch(error => {
            this._view.showError(error.message)
        });
    }

    _getRandomUnsortedPostId() {
        let unsetMetricsQuery = this._metricNames
            .map(m => `${m} -metric:${m}`)
            .join(' ');
        let filterQuery = this._ctx.parameters.query || '';
        let unsetFullQuery = `${filterQuery} ${unsetMetricsQuery} sort:random`;

        return PostList.search(unsetFullQuery,
                this._ctx.parameters.skips || 0, 1, ['id']).then(response => {
            if (!response.results.length) {
                return Promise.reject(new Error('No posts found'));
            } else {
                return Promise.resolve(response.results.at(0).id);
            }
        });
    }

    _tryGetMedianPost(metric, range) {
        let low = range.low + 0.000000001;
        let high = range.high - 0.000000001;
        let median_query = `metric-${metric}:${low}..${high} sort:metric-${metric}`;
        return PostList.getMedian(median_query, []).then(response => {
            return Promise.resolve({
                range: range,
                post: response.results.at(0)
            });
        });
    }

    _getOrCreateRange(post, metricName) {
        let range = post.metricRanges.findByTagName(metricName);
        if (!range) {
            let tag = post.tags.findByName(metricName);
            range = PostMetricRange.create(post.id, tag);
            post.metricRanges.add(range);
        }
        return range;
    }

    _setExactMetric(post, metricName, value) {
        let range = post.metricRanges.findByTagName(metricName);
        if (!range) {
            post.metricRanges.remove(range);
        }
        let tag = post.tags.findByName(metricName);
        let exactMetric = PostMetric.create(post.id, tag);
        exactMetric.value = value;
        post.metrics.add(exactMetric);
    }

    _evtSubmit(e) {
        let range = this._getOrCreateRange(this._unsortedPost, this._primaryMetricName);
        if (this._foundExactValue) {
            this._unsortedPost.metricRanges.remove(range);
        } else {
            let medianValue = this._sortedPost.metrics.findByTagName(this._primaryMetricName).value;
            if (e.detail.greaterPost === LEFT) {
                range.low = medianValue;
            } else {
                range.high = medianValue;
            }
        }
        this._unsortedPost.save().then(() => {
            if (this._foundExactValue) {
                this.startSortingRandomPost();
            } else {
                this.reloadMedianPost();
            }
        }, error => {
            this._view.showError(error.message)
        });
    }

    _evtSkip(e) {
        this._ctx.parameters.skips = (this._ctx.parameters.skips || 0) + 1;
        this.startSortingRandomPost();
    }

    _evtChangeMetric(e) {
        // this._primaryMetricName = e.detail.metricName;
    }
}

module.exports = router => {
    router.enter(
        ['post', ':id', 'metric-sorter'],
        (ctx, next) => {
            ctx.controller = new MetricSorterController(ctx);
        });
};
