'use strict';

const api = require('../api.js');
const uri = require('../util/uri.js');
const AbstractList = require('./abstract_list.js');
const Metric = require('./metric.js');

class MetricList extends AbstractList {
    static loadAll() {
        return api.get(
            uri.formatApiLink('metrics'))
            .then(response => {
                return Promise.resolve(Object.assign(
                    {},
                    response,
                    {results: MetricList.fromResponse(response.results)}));
            });
    }
}

MetricList._itemClass = Metric;
MetricList._itemName = 'metric';

module.exports = MetricList;
