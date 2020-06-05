"use strict";

const views = require("../util/views.js");

const template = () => {
    return views.htmlToDom(
        '<div class="wrapper"><div class="messages"></div></div>'
    );
};

class EmptyView {
    constructor() {
        this._hostNode = document.getElementById("content-holder");
        views.replaceContent(this._hostNode, template());
        views.syncScrollPosition();
    }

    showError(message) {
        views.showError(this._hostNode, message);
    }
}

module.exports = EmptyView;
