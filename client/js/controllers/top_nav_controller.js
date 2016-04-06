'use strict';

const api = require('../api.js');
const TopNavView = require('../views/top_nav_view.js');

class NavigationItem {
    constructor(accessKey, name, url) {
        this.accessKey = accessKey;
        this.name = name;
        this.url = url;
        this.available = true;
    }
}

class TopNavController {
    constructor() {
        this.topNavView = new TopNavView();
        this.activeItem = null;

        this.items = {
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
        };

        api.authenticated.listen(() => {
            this.updateVisibility();
            this.topNavView.render(this.items, this.activeItem);
            this.topNavView.activate(this.activeItem);
        });

        this.updateVisibility();
        this.topNavView.render(this.items, this.activeItem);
        this.topNavView.activate(this.activeItem);
    }

    updateVisibility() {
        this.items['account'].url =  '/user/' + api.userName;

        const b = Object.keys(this.items);
        for (let key of b) {
            this.items[key].available = true;
        }
        if (!api.hasPrivilege('posts:list')) {
            this.items.posts.available = false;
        }
        if (!api.hasPrivilege('posts:create')) {
            this.items.upload.available = false;
        }
        if (!api.hasPrivilege('comments:list')) {
            this.items.comments.available = false;
        }
        if (!api.hasPrivilege('tags:list')) {
            this.items.tags.available = false;
        }
        if (!api.hasPrivilege('users:list')) {
            this.items.users.available = false;
        }
        if (api.isLoggedIn()) {
            this.items.register.available = false;
            this.items.login.available = false;
        } else {
            this.items.account.available = false;
            this.items.logout.available = false;
        }
    }

    activate(itemName) {
        this.activeItem = itemName;
        this.topNavView.activate(this.activeItem);
    }
}

module.exports = new TopNavController();
