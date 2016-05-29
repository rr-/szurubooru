'use strict';

const page = require('page');
const config = require('../config.js');
const misc = require('../util/misc.js');
const views = require('../util/views.js');
const PostContentControl = require('../controls/post_content_control.js');
const TagAutoCompleteControl =
    require('../controls/tag_auto_complete_control.js');

class HomeView {
    constructor() {
        this._homeTemplate = views.getTemplate('home');
    }

    render(ctx) {
        Object.assign(ctx, {
            name: config.name,
            version: config.meta.version,
            buildDate: config.meta.buildDate,
        });
        const target = document.getElementById('content-holder');
        const source = this._homeTemplate(ctx);

        views.listenToMessages(source);
        views.showView(target, source);

        const form = source.querySelector('form');
        if (form) {
            const searchTextInput = form.querySelector('input');
            new TagAutoCompleteControl(searchTextInput);
            form.addEventListener('submit', e => {
                e.preventDefault();
                const text = searchTextInput.value;
                searchTextInput.blur();
                page('/posts/' + misc.formatSearchQuery({text: text}));
            });
        }

        const postContainerNode = source.querySelector('.post-container');

        if (postContainerNode && ctx.featuredPost) {
            new PostContentControl(
                postContainerNode,
                ctx.featuredPost,
                () => {
                    return [
                        window.innerWidth * 0.8,
                        window.innerHeight * 0.7,
                    ];
                });
        }
    }
}

module.exports = HomeView;
