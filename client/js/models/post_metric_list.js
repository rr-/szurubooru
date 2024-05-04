'use strict';

const AbstractList = require('./abstract_list.js');
const PostMetric = require('./post_metric.js');

class PostMetricList extends AbstractList {
    findByTagName(testName) {
        for (let postMetric of this._list) {
            if (postMetric.tagName.toLowerCase() === testName.toLowerCase()) {
                return postMetric;
            }
        }
        return null;
    }

    hasTagName(testName) {
        return !!this.findByTagName(testName);
    }
}

PostMetricList._itemClass = PostMetric;
PostMetricList._itemName = 'postMetric';

module.exports = PostMetricList;
