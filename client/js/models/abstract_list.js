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
            if (addedItem.addEventListener) {
                addedItem.addEventListener('delete', e => {
                    ret.remove(addedItem);
                });
                addedItem.addEventListener('change', e => {
                    ret.dispatchEvent(new CustomEvent('change', {
                        detail: e.detail,
                    }));
                });
            }
            ret._list.push(addedItem);
        }
        return ret;
    }

    sync(plainList) {
        this.clear();
        for (let item of (plainList || [])) {
            this.add(this.constructor._itemClass.fromResponse(item));
        }
    }

    add(item) {
        if (item.addEventListener) {
            item.addEventListener('delete', e => {
                this.remove(item);
            });
            item.addEventListener('change', e => {
                this.dispatchEvent(new CustomEvent('change', {
                    detail: e.detail,
                }));
            });
        }
        this._list.push(item);
        const detail = {};
        detail[this.constructor._itemName] = item;
        this.dispatchEvent(new CustomEvent('add', {
            detail: detail,
        }));
    }

    clear() {
        for (let item of [...this._list]) {
            this.remove(item);
        }
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

    at(index) {
        return this._list[index];
    }

    map(...args) {
        return this._list.map(...args);
    }

    filter(...args) {
        return this._list.filter(...args);
    }

    [Symbol.iterator]() {
        return this._list[Symbol.iterator]();
    }
}

module.exports = AbstractList;
