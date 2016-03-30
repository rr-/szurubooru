'use strict';

class NavigationItem {
    constructor(name, url) {
        this.name = name;
        this.url = url;
        this.available = true;
    }
}

class TopNavigationController {
    constructor(topNavigationView, api) {
        this.api = api;
        this.topNavigationView = topNavigationView;
        this.activeItem = null;

        this.items = {
            'home':     new NavigationItem('Home',     '/'),
            'posts':    new NavigationItem('Posts',    '/posts'),
            'upload':   new NavigationItem('Upload',   '/upload'),
            'comments': new NavigationItem('Comments', '/comments'),
            'tags':     new NavigationItem('Tags',     '/tags'),
            'users':    new NavigationItem('Users',    '/users'),
            'account':  new NavigationItem('Account',  '/user/{me}'),
            'register': new NavigationItem('Register', '/register'),
            'login':    new NavigationItem('Log in',   '/login'),
            'logout':   new NavigationItem('Logout',   '/logout'),
            'help':     new NavigationItem('Help',     '/help'),
        };

        this.api.authenticated.listen(() => {
            this.updateVisibility();
            this.topNavigationView.render(this.items, this.activeItem);
            this.topNavigationView.activate(this.activeItem);
        });

        this.updateVisibility();
        this.topNavigationView.render(this.items, this.activeItem);
        this.topNavigationView.activate(this.activeItem);
    }

    updateVisibility() {
        const b = Object.keys(this.items);
        for (let key of b) {
            this.items[key].available = true;
        }
        if (!this.api.hasPrivilege('posts:list')) {
            this.items.posts.available = false;
        }
        if (!this.api.hasPrivilege('posts:create')) {
            this.items.upload.available = false;
        }
        if (!this.api.hasPrivilege('comments:list')) {
            this.items.comments.available = false;
        }
        if (!this.api.hasPrivilege('tags:list')) {
            this.items.tags.available = false;
        }
        if (!this.api.hasPrivilege('users:list')) {
            this.items.users.available = false;
        }
        if (this.api.isLoggedIn()) {
            this.items.register.available = false;
            this.items.login.available = false;
        } else {
            this.items.account.available = false;
            this.items.logout.available = false;
        }
    }

    activate(itemName) {
        this.activeItem = itemName;
        this.topNavigationView.activate(this.activeItem);
    }
}

module.exports = TopNavigationController;
