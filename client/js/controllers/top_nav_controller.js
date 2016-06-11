'use strict';

const api = require('../api.js');
const events = require('../events.js');
const TopNavView = require('../views/top_nav_view.js');

function _createNavigationItemMap() {
    const ret = new Map();
    ret.set('home',     new NavigationItem('H', 'Home',     '/'));
    ret.set('posts',    new NavigationItem('P', 'Posts',    '/posts'));
    ret.set('upload',   new NavigationItem('U', 'Upload',   '/upload'));
    ret.set('comments', new NavigationItem('C', 'Comments', '/comments'));
    ret.set('tags',     new NavigationItem('T', 'Tags',     '/tags'));
    ret.set('users',    new NavigationItem('S', 'Users',    '/users'));
    ret.set('account',  new NavigationItem('A', 'Account',  '/user/{me}'));
    ret.set('register', new NavigationItem('R', 'Register', '/register'));
    ret.set('login',    new NavigationItem('L', 'Log in',   '/login'));
    ret.set('logout',   new NavigationItem('O', 'Logout',   '/logout'));
    ret.set('help',     new NavigationItem('E', 'Help',     '/help'));
    ret.set(
        'settings',
        new NavigationItem(null, '<i class=\'fa fa-cog\'></i>', '/settings'));
    return ret;
}

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
        this._items = _createNavigationItemMap();

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
        this._items.get('account').url =  '/user/' + api.userName;
        this._items.get('account').imageUrl = api.user ?
            api.user.avatarUrl : null;

        for (let [key, item] of this._items) {
            item.available = true;
        }
        if (!api.hasPrivilege('posts:list')) {
            this._items.get('posts').available = false;
        }
        if (!api.hasPrivilege('posts:create')) {
            this._items.get('upload').available = false;
        }
        if (!api.hasPrivilege('comments:list')) {
            this._items.get('comments').available = false;
        }
        if (!api.hasPrivilege('tags:list')) {
            this._items.get('tags').available = false;
        }
        if (!api.hasPrivilege('users:list')) {
            this._items.get('users').available = false;
        }
        if (api.isLoggedIn()) {
            this._items.get('register').available = false;
            this._items.get('login').available = false;
        } else {
            this._items.get('account').available = false;
            this._items.get('logout').available = false;
        }
    }

    activate(itemName) {
        this._activeItem = itemName;
        this._topNavView.activate(this._activeItem);
    }
}

module.exports = new TopNavController();
