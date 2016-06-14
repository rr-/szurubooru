'use strict';

const router = require('../router.js');
const misc = require('../util/misc.js');
const views = require('../util/views.js');
const PostContentControl = require('../controls/post_content_control.js');
const PostNotesOverlayControl
    = require('../controls/post_notes_overlay_control.js');
const TagAutoCompleteControl =
    require('../controls/tag_auto_complete_control.js');

const template = views.getTemplate('home');
const statsTemplate = views.getTemplate('home-stats');

class HomeView {
    constructor(ctx) {
        this._hostNode = document.getElementById('content-holder');

        const sourceNode = template(ctx);
        views.replaceContent(this._hostNode, sourceNode);

        if (this._formNode) {
            this._formNode.querySelector('input[name=all-posts')
                .addEventListener('click', e => this._evtAllPostsClick(e));

            this._tagAutoCompleteControl = new TagAutoCompleteControl(
                this._searchInputNode);
            this._formNode.addEventListener(
                'submit', e => this._evtFormSubmit(e));
        }

    }

    showSuccess(text) {
        views.showSuccess(this._hostNode, text);
    }

    showError(text) {
        views.showError(this._hostNode, text);
    }

    setStats(stats) {
        views.replaceContent(this._statsContainerNode, statsTemplate(stats));
    }

    setFeaturedPost(postInfo) {
        if (this._postContainerNode && postInfo.featuredPost) {
            this._postContentControl = new PostContentControl(
                this._postContainerNode,
                postInfo.featuredPost,
                () => {
                    return [
                        window.innerWidth * 0.8,
                        window.innerHeight * 0.7,
                    ];
                });

            this._postNotesOverlay = new PostNotesOverlayControl(
                this._postContainerNode.querySelector('.post-overlay'),
                postInfo.featuredPost);
        }
    }

    get _statsContainerNode() {
        return this._hostNode.querySelector('.stats-container');
    }

    get _postContainerNode() {
        return this._hostNode.querySelector('.post-container');
    }

    get _formNode() {
        return this._hostNode.querySelector('form');
    }

    get _searchInputNode() {
        return this._formNode.querySelector('input[name=search-text]');
    }

    _evtAllPostsClick(e) {
        e.preventDefault();
        router.show('/posts/');
    }

    _evtFormSubmit(e) {
        e.preventDefault();
        this._searchInputNode.blur();
        router.show('/posts/' + misc.formatSearchQuery({
            text: this._searchInputNode.value}));
    }
}

module.exports = HomeView;
