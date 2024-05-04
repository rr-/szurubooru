'use strict';

const events = require('../events.js');
const views = require('../util/views.js');

const postMetricNodeTemplate = views.getTemplate('compact-post-metric-list-item');
const postMetricRangeNodeTemplate = views.getTemplate('compact-post-metric-range-list-item');

class PostMetricListControl extends events.EventTarget {
    constructor(listNode, post) {
        super();
        this._post = post;
        this._listNode = listNode;

        this._refreshContent();
    }

    _refreshContent() {
        this._listNode.innerHTML = '';
        for (let pm of this._post.metrics) {
            const postMetricNode = this._createPostMetricNode(pm);
            this._listNode.appendChild(postMetricNode);
        }
        for (let pmr of this._post.metricRanges) {
            const postMetricRangeNode = this._createPostMetricRangeNode(pmr);
            this._listNode.appendChild(postMetricRangeNode);
        }
    }

    _createPostMetricNode(pm) {
        const tag = this._post.tags.findByName(pm.tagName);
        const node = postMetricNodeTemplate({
            editMode: false,
            postMetric: pm,
            tag: tag,
        });
        return node;
    }

    _createPostMetricRangeNode(pmr) {
        const tag = this._post.tags.findByName(pmr.tagName);
        const node = postMetricRangeNodeTemplate({
            editMode: false,
            postMetricRange: pmr,
            tag: tag,
        });
        return node;
    }
}

module.exports = PostMetricListControl;
