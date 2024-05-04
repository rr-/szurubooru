'use strict';

const AbstractList = require('./abstract_list.js');
const PostMetricRange = require('./post_metric_range.js');

class PostMetricRangeList extends AbstractList {
    findByTagName(testName) {
        for (let pmr of this._list) {
            if (pmr.tagName.toLowerCase() === testName.toLowerCase()) {
                return pmr;
            }
        }
        return null;
    }

    hasTagName(testName) {
        return !!this.findByTagName(testName);
    }
}

PostMetricRangeList._itemClass = PostMetricRange;
PostMetricRangeList._itemName = 'postMetricRange';

module.exports = PostMetricRangeList;
