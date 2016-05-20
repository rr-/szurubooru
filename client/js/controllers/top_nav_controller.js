'use strict';

const api = require('../api.js');
const events = require('../events.js');
const TopNavView = require('../views/top_nav_view.js');

class NavigationItem {
    constructor(accessKey, name, url) {
        this.accessKey = accessKey;
        this.name = name;
        this.url = url;
        this.available = true;
        this.imageUrl = null;
    }
}

class TopNavController {
    constructor() {
        this._topNavView = new TopNavView();
        this._activeItem = null;

        this._items = {
            'home':     new NavigationItem('H', 'Home',     '/'),
            'posts':    new NavigationItem('P', 'Posts',    '/posts'),
            'upload':   new NavigationItem('U', 'Upload',   '/upload'),
            'comments': new NavigationItem('C', 'Comments', '/comments'),
            'tags':     new NavigationItem('T', 'Tags',     '/tags'),
            'users':    new NavigationItem('S', 'Users',    '/users'),
            'account':  new NavigationItem('A', 'Account',  '/user/{me}'),
            'register': new NavigationItem('R', 'Register', '/register'),
            'login':    new NavigationItem('L', 'Log in',   '/login'),
            'logout':   new NavigationItem('O', 'Logout',   '/logout'),
            'help':     new NavigationItem('E', 'Help',     '/help'),
            'settings': new NavigationItem(
                null, '<i class=\'fa fa-cog\'></i>', '/settings'),
        };

        const rerender = () => {
            this._updateVisibility();
            this._topNavView.render({
                items: this._items,
                activeItem: this._activeItem});
            this._topNavView.activate(this._activeItem);
        };

        events.listen(
            events.Authentication,
            () => { rerender(); return true; });
        rerender();
    }

    _updateVisibility() {
        this._items.account.url =  '/user/' + api.userName;
        this._items.account.imageUrl = api.user ? api.user.avatarUrl : null;

        const b = Object.keys(this._items);
        for (let key of b) {
            this._items[key].available = true;
        }
        if (!api.hasPrivilege('posts:list')) {
            this._items.posts.available = false;
        }
        if (!api.hasPrivilege('posts:create')) {
            this._items.upload.available = false;
        }
        if (!api.hasPrivilege('comments:list')) {
            this._items.comments.available = false;
        }
        if (!api.hasPrivilege('tags:list')) {
            this._items.tags.available = false;
        }
        if (!api.hasPrivilege('users:list')) {
            this._items.users.available = false;
        }
        if (api.isLoggedIn()) {
            this._items.register.available = false;
            this._items.login.available = false;
        } else {
            this._items.account.available = false;
            this._items.logout.available = false;
        }
    }

    activate(itemName) {
        this._activeItem = itemName;
        this._topNavView.activate(this._activeItem);
    }
}

module.exports = new TopNavController();
