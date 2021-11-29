"use strict";

const ICON_CLASS_OPENED = "fa-chevron-down";
const ICON_CLASS_CLOSED = "fa-chevron-up";

const views = require("../util/views.js");

const template = views.getTemplate("expander");

class ExpanderControl {
    constructor(name, title, nodes) {
        this._name = name;

        nodes = Array.from(nodes).filter((n) => n);
        if (!nodes.length) {
            return;
        }

        const expanderNode = template({ title: title });
        const toggleLinkNode = expanderNode.querySelector("a");
        const toggleIconNode = expanderNode.querySelector("i");
        const expanderContentNode = expanderNode.querySelector("div");
        toggleLinkNode.addEventListener("click", (e) =>
            this._evtToggleClick(e)
        );

        nodes[0].parentNode.insertBefore(expanderNode, nodes[0]);

        for (let node of nodes) {
            expanderContentNode.appendChild(node);
        }

        this._expanderNode = expanderNode;
        this._toggleIconNode = toggleIconNode;

        expanderNode.classList.toggle(
            "collapsed",
            this._allStates[this._name] === undefined
                ? false
                : !this._allStates[this._name]
        );
        this._syncIcon();
    }

    // eslint-disable-next-line accessor-pairs
    set title(newTitle) {
        if (this._expanderNode) {
            this._expanderNode.querySelector("header span").textContent =
                newTitle;
        }
    }

    get _isOpened() {
        return !this._expanderNode.classList.contains("collapsed");
    }

    get _allStates() {
        try {
            return JSON.parse(localStorage.getItem("expander")) || {};
        } catch (e) {
            return {};
        }
    }

    _save() {
        const newStates = Object.assign({}, this._allStates);
        newStates[this._name] = this._isOpened;
        localStorage.setItem("expander", JSON.stringify(newStates));
    }

    _evtToggleClick(e) {
        e.preventDefault();
        this._expanderNode.classList.toggle("collapsed");
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
