'use strict';

const views = require('../util/views.js');

class EmptyView {
    constructor() {
        this._template = () => {
            return views.htmlToDom(
                '<div class="wrapper"><div class="messages"></div></div>');
        };
    }

    render(ctx) {
        const target = document.getElementById('content-holder');
        const source = this._template();
        views.listenToMessages(source);
        views.showView(target, source);
    }
}

module.exports = EmptyView;
