'use strict';

const ICON_CLASS_OPENED = 'fa-chevron-down';
const ICON_CLASS_CLOSED = 'fa-chevron-up';

class ExpanderControl {
    constructor(title, nodes) {
        this._title = title;

        nodes = Array.from(nodes).filter(n => n);
        if (!nodes.length) {
            return;
        }

        const expanderNode = document.createElement('section');
        expanderNode.classList.add('expander');

        const toggleLinkNode = document.createElement('a');
        const toggleIconNode = document.createElement('i');
        toggleIconNode.classList.add('fa');
        toggleLinkNode.textContent = title;
        toggleLinkNode.appendChild(toggleIconNode);
        toggleLinkNode.addEventListener('click', e => this._evtToggleClick(e));

        const headerNode = document.createElement('header');
        headerNode.appendChild(toggleLinkNode);
        expanderNode.appendChild(headerNode);

        const expanderContentNode = document.createElement('div');
        expanderContentNode.classList.add('expander-content');
        expanderNode.appendChild(expanderContentNode);

        nodes[0].parentNode.insertBefore(expanderNode, nodes[0]);

        for (let node of nodes) {
            expanderContentNode.appendChild(node);
        }

        this._expanderNode = expanderNode;
        this._toggleIconNode = toggleIconNode;

        expanderNode.classList.toggle(
            'collapsed',
            this._allStates[this._title] === undefined ?
                false :
                !this._allStates[this._title]);
        this._syncIcon();
    }

    get _isOpened() {
        return !this._expanderNode.classList.contains('collapsed');
    }

    get _allStates() {
        try {
            return JSON.parse(localStorage.getItem('expander')) || {};
        } catch (e) {
            return {};
        }
    }

    _save() {
        const newStates = Object.assign({}, this._allStates);
        newStates[this._title] = this._isOpened;
        localStorage.setItem('expander', JSON.stringify(newStates));
    }

    _evtToggleClick(e) {
        e.preventDefault();
        this._expanderNode.classList.toggle('collapsed');
        this._save();
        this._syncIcon();
    }

    _syncIcon() {
        if (this._isOpened) {
            this._toggleIconNode.classList.add(ICON_CLASS_OPENED);
            this._toggleIconNode.classList.remove(ICON_CLASS_CLOSED);
        } else {
            this._toggleIconNode.classList.add(ICON_CLASS_CLOSED);
            this._toggleIconNode.classList.remove(ICON_CLASS_OPENED);
        }
    }
}

module.exports = ExpanderControl;
