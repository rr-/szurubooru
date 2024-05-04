'use strict';

const events = require('../events.js');

class PostMetric extends events.EventTarget {
    constructor() {
        super();
        this._updateFromResponse({});
    }

    static create(postId, tag) {
        const metric = new PostMetric();
        metric._postId = postId;
        metric._tagName = tag.names[0];
        metric._value = tag.metric.min;
        return metric;
    }

    static fromResponse(response) {
        const metric = new PostMetric();
        metric._updateFromResponse(response);
        return metric;
    }

    get tagName()    { return this._tagName; }
    get postId()     { return this._postId; }
    get value()      { return this._value; }

    set value(value) { this._value = value; }

    _updateFromResponse(response) {
        this._version      = response.version;
        this._postId       = response.post_id;
        this._tagName      = response.tag_name;
        this._value        = response.value;
    }
}

module.exports = PostMetric;