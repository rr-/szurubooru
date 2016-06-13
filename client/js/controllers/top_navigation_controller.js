'use strict';

const api = require('../api.js');
const events = require('../events.js');
const TopNavigationView = require('../views/top_navigation_view.js');
const TopNavigation = require('../models/top_navigation.js');

class TopNavigationController {
    constructor() {
        this._topNavigationView = new TopNavigationView();

        TopNavigation.addEventListener(
            'activate', e => this._evtActivate(e));

        events.listen(
            events.Authentication,
            () => {
                this._render();
                return true;
            });

        this._render();
    }

    _evtActivate(e) {
        this._topNavigationView.activate(e.key);
    }

    _updateNavigationFromPrivileges() {
        TopNavigation.get('account').url = '/user/' + api.userName;
        TopNavigation.get('account').imageUrl =
            api.user ? api.user.avatarUrl : null;

        TopNavigation.showAll();
        if (!api.hasPrivilege('posts:list')) {
            TopNavigation.hide('posts');
        }
        if (!api.hasPrivilege('posts:create')) {
            TopNavigation.hide('upload');
        }
        if (!api.hasPrivilege('comments:list')) {
            TopNavigation.hide('comments');
        }
        if (!api.hasPrivilege('tags:list')) {
            TopNavigation.hide('tags');
        }
        if (!api.hasPrivilege('users:list')) {
            TopNavigation.hide('users');
        }
        if (api.isLoggedIn()) {
            TopNavigation.hide('register');
            TopNavigation.hide('login');
        } else {
            TopNavigation.hide('account');
            TopNavigation.hide('logout');
        }
    }

    _render() {
        this._updateNavigationFromPrivileges();
        console.log(TopNavigation.getAll());
        this._topNavigationView.render({
            items: TopNavigation.getAll(),
        });
        this._topNavigationView.activate(
            TopNavigation.activeItem ? TopNavigation.activeItem.key : '');
    };
}

module.exports = new TopNavigationController();
