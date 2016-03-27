'use strict';

class AuthController {
    constructor(topNavigationController, loginView) {
        this.topNavigationController = topNavigationController;
        this.loginView = loginView;
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
        this.loginView.render({
            login: (user, password) => {
                alert(user, password);
                //self.authController.login(user);
            }});
    }

    logoutRoute() {
        this.topNavigationController.activate('logout');
    }
}

module.exports = AuthController;
