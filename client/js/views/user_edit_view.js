'use strict';

const BaseView = require('./base_view.js');

class UserEditView extends BaseView {
    constructor() {
        super();
        this.template = this.getTemplate('user-edit-template');
    }

    render(options) {
        options.target.innerHTML = this.template(options.user);
    }
}

module.exports = UserEditView;
