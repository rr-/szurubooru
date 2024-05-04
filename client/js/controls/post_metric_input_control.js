'use strict';

const uri = require('../util/uri.js');
const PostMetric = require('../models/post_metric.js');
const PostMetricRange = require('../models/post_metric_range.js');
const events = require('../events.js');
const views = require('../util/views.js');

const mainTemplate = views.getTemplate('post-metric-input');
const metricNodeTemplate = views.getTemplate('compact-metric-list-item');
const postMetricNodeTemplate = views.getTemplate('compact-post-metric-list-item');
const postMetricRangeNodeTemplate = views.getTemplate('compact-post-metric-range-list-item');

class PostMetricInputControl extends events.EventTarget {
    constructor(hostNode, ctx) {
        super();
        this._ctx = ctx;
        this._post = ctx.post;
        this._hostNode = hostNode;

        // dom
        const editAreaNode = mainTemplate({
            tags: this._post.tags,
            postMetrics: this._post.metrics,
        });
        this._editAreaNode = editAreaNode;
        this._metricListNode = editAreaNode.querySelector('ul.compact-unset-metrics');
        this._separatorNode = editAreaNode.querySelector('hr.separator');
        this._postMetricListNode = editAreaNode.querySelector('ul.compact-post-metrics');

        // show
        this._hostNode.style.display = 'none';
        this._hostNode.parentNode.insertBefore(
            this._editAreaNode, hostNode.nextSibling);

        // add existing metrics and post metrics:
        this.refreshContent();
    }

    refreshContent() {
        this._metricListNode.innerHTML = '';
        for (let tag of this._post.tags.filterMetrics()) {
            const metricNode = this._createMetricNode(tag);
            this._metricListNode.appendChild(metricNode);
        }
        this._postMetricListNode.innerHTML = '';
        for (let pm of this._post.metrics) {
            const postMetricNode = this._createPostMetricNode(pm);
            this._postMetricListNode.appendChild(postMetricNode);
        }
        for (let pmr of this._post.metricRanges) {
            const postMetricRangeNode = this._createPostMetricRangeNode(pmr);
            this._postMetricListNode.appendChild(postMetricRangeNode);
        }
        this._separatorNode.style.display =
            this._postMetricListNode.innerHTML ? 'block' : 'none';
    }

    _createMetricNode(tag) {
        const node = metricNodeTemplate({
            editMode: true,
            tag: tag,
            post: this._post,
            query: this._ctx.parameters.query,
        });
        const createExactNode = node.querySelector('a.create-exact');
        if (this._post.metrics.hasTagName(tag.names[0])) {
            createExactNode.style.display = 'none';
        } else {
            createExactNode.addEventListener('click', e => {
                e.preventDefault();
                this.createPostMetric(tag);
            });
        }
        const createRangeNode = node.querySelector('a.create-range');
        if (this._post.metricRanges.hasTagName(tag.names[0])) {
            createRangeNode.style.display = 'none';
        } else {
            createRangeNode.addEventListener('click', e => {
                e.preventDefault();
                this.createPostMetricRange(tag);
            });
        }
        const sortNode = node.querySelector('a.sort');
        if (this._post.metrics.hasTagName(tag.names[0])) {
            sortNode.style.display = 'none';
        }
        return node;
    }

    _createPostMetricNode(pm) {
        const tag = this._post.tags.findByName(pm.tagName);
        const node = postMetricNodeTemplate({
            editMode: true,
            postMetric: pm,
            tag: tag,
        });
        node.querySelector('input[name=value]').addEventListener('change', e => {
            pm.value = e.target.value;
            this.dispatchEvent(new CustomEvent('change'));
        });
        node.querySelector('.remove-metric').addEventListener('click', e => {
            e.preventDefault();
            this.deletePostMetric(pm);
        });
        return node;
    }

    _createPostMetricRangeNode(pmr) {
        const tag = this._post.tags.findByName(pmr.tagName);
        const node = postMetricRangeNodeTemplate({
            editMode: true,
            postMetricRange: pmr,
            tag: tag,
        });
        node.querySelector('input[name=low]').addEventListener('change', e => {
            pmr.low = e.target.value;
            this.dispatchEvent(new CustomEvent('change'));
        });
        node.querySelector('input[name=high]').addEventListener('change', e => {
            pmr.high = e.target.value;
            this.dispatchEvent(new CustomEvent('change'));
        });
        node.querySelector('.remove-metric').addEventListener('click', e => {
            e.preventDefault();
            this.deletePostMetricRange(pmr);
        });
        return node;
    }

    createPostMetric(tag) {
        let postMetricRange = this._post.metricRanges.findByTagName(tag.names[0]);
        if (postMetricRange) {
            this._post.metricRanges.remove(postMetricRange);
        }
        this._post.metrics.add(PostMetric.create(this._post.id, tag));
        this.refreshContent();
        this.dispatchEvent(new CustomEvent('change'));
    }

    createPostMetricRange(tag) {
        let postMetric = this._post.metrics.findByTagName(tag.names[0]);
        if (postMetric) {
            this._post.metrics.remove(postMetric);
        }
        this._post.metricRanges.add(PostMetricRange.create(this._post.id, tag));
        this.refreshContent();
        this.dispatchEvent(new CustomEvent('change'));
    }

    deletePostMetric(pm) {
        this._post.metrics.remove(pm);
        this.refreshContent();
        this.dispatchEvent(new CustomEvent('change'));
    }

    deletePostMetricRange(pmr) {
        this._post.metricRanges.remove(pmr);
        this.refreshContent();
        this.dispatchEvent(new CustomEvent('change'));
    }
}

module.exports = PostMetricInputControl;
