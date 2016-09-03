'use strict';

const api = require('../api.js');
const AbstractList = require('./abstract_list.js');
const Snapshot = require('./snapshot.js');

class SnapshotList extends AbstractList {
    static search(text, page, pageSize) {
        const url =
            `/snapshots/?query=${encodeURIComponent(text)}` +
            `&page=${page}` +
            `&pageSize=${pageSize}`;
        return api.get(url).then(response => {
            return Promise.resolve(Object.assign(
                {},
                response,
                {results: SnapshotList.fromResponse(response.results)}));
        });
    }
}

SnapshotList._itemClass = Snapshot;
SnapshotList._itemName = 'snapshot';

module.exports = SnapshotList;
