'use strict';

const events = require('../events.js');

class PostMetricRange extends events.EventTarget {
    constructor() {
        super();
        this._updateFromResponse({});
    }

    static create(postId, tag) {
        const metric = new PostMetricRange();
        metric._postId = postId;
        metric._tagName = tag.names[0];
        metric._low = tag.metric.min;
        metric._high = tag.metric.max;
        return metric;
    }

    static fromResponse(response) {
        const metric = new PostMetricRange();
        metric._updateFromResponse(response);
        return metric;
    }

    get tagName()    { return this._tagName; }
    get postId()     { return this._postId; }
    get low()        { return this._low; }
    get high()       { return this._high; }

    set low(value)   { this._low = value; }
    set high(value)  { this._high = value; }

    _updateFromResponse(response) {
        this._version  = response.version;
        this._postId   = response.post_id;
        this._tagName  = response.tag_name;
        this._low      = response.low;
        this._high     = response.high;
    }
}

module.exports = PostMetricRange;