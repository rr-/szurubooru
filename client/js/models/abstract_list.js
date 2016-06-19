'use strict';

const events = require('../events.js');

class AbstractList extends events.EventTarget {
    constructor() {
        super();
        this._list = [];
    }

    static fromResponse(response) {
        const ret = new this();
        for (let item of response) {
            const addedItem = this._itemClass.fromResponse(item);
            addedItem.addEventListener('delete', e => {
                ret.remove(addedItem);
            });
            ret._list.push(addedItem);
        }
        return ret;
    }

    add(item) {
        item.addEventListener('delete', e => {
            this.remove(item);
        });
        this._list.push(item);
        const detail = {};
        detail[this.constructor._itemName] = item;
        this.dispatchEvent(new CustomEvent('add', {
            detail: detail,
        }));
    }

    remove(itemToRemove) {
        for (let [index, item] of this._list.entries()) {
            if (item !== itemToRemove) {
                continue;
            }
            this._list.splice(index, 1);
            const detail = {};
            detail[this.constructor._itemName] = itemToRemove;
            this.dispatchEvent(new CustomEvent('remove', {
                detail: detail,
            }));
            return;
        }
    }

    get length() {
        return this._list.length;
    }

    [Symbol.iterator]() {
        return this._list[Symbol.iterator]();
    }
}

module.exports = AbstractList;
