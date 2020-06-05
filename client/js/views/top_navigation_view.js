"use strict";

const views = require("../util/views.js");

const template = views.getTemplate("top-navigation");

class TopNavigationView {
    constructor() {
        this._hostNode = document.getElementById("top-navigation-holder");
    }

    get _mobileNavigationToggleNode() {
        return this._hostNode.querySelector("#mobile-navigation-toggle");
    }

    get _navigationListNode() {
        return this._hostNode.querySelector("nav > ul");
    }

    get _navigationLinkNodes() {
        return this._navigationListNode.querySelectorAll("li > a");
    }

    render(ctx) {
        views.replaceContent(this._hostNode, template(ctx));

        this._bindMobileNavigationEvents();
    }

    activate(key) {
        for (let itemNode of this._hostNode.querySelectorAll("[data-name]")) {
            itemNode.classList.toggle(
                "active",
                itemNode.getAttribute("data-name") === key
            );
        }
    }

    _bindMobileNavigationEvents() {
        this._mobileNavigationToggleNode.addEventListener("click", (e) =>
            this._mobileNavigationToggleClick(e)
        );

        for (let navigationLinkNode of this._navigationLinkNodes) {
            navigationLinkNode.addEventListener("click", (e) =>
                this._navigationLinkClick(e)
            );
        }
    }

    _mobileNavigationToggleClick(e) {
        this._navigationListNode.classList.toggle("opened");
    }

    _navigationLinkClick(e) {
        this._navigationListNode.classList.remove("opened");
    }
}

module.exports = TopNavigationView;
