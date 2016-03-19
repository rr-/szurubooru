'use strict';

class AuthController {
    constructor(topNavigationController) {
        this.topNavigationController = topNavigationController;
        this.currentUser = null;
    }

    isLoggedIn() {
        return this.currentUser !== null;
    }

    hasPrivilege() {
        return true;
    }

    login(user) {
        this.currentUser = user;
    }

    logout(user) {
        this.currentUser = null;
    }

    loginRoute() {
        this.topNavigationController.activate('login');
    }

    logoutRoute() {
        this.topNavigationController.activate('logout');
    }
}

module.exports = AuthController;
