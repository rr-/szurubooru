'use strict';

const api = require('../api.js');
const AbstractList = require('./abstract_list.js');
const User = require('./user.js');

class UserList extends AbstractList {
    static search(text, page) {
        const url =
            `/users/?query=${encodeURIComponent(text)}` +
            `&page=${page}&pageSize=30`;
        return api.get(url).then(response => {
            return Promise.resolve(Object.assign(
                {},
                response,
                {results: UserList.fromResponse(response.results)}));
        });
    }
}

UserList._itemClass = User;
UserList._itemName = 'user';

module.exports = UserList;
