'use strict';

class UsersController {
    constructor(topNavigationController, authController, registrationView) {
        this.topNavigationController = topNavigationController;
        this.authController = authController;
        this.registrationView = registrationView;
    }

    listUsersRoute() {
        this.topNavigationController.activate('users');
    }

    createUserRoute() {
        const self = this;
        this.topNavigationController.activate('register');
        this.registrationView.render({
            register: (user) => {
                alert(user);
                self.authController.login(user);
            }});
    }

    showUserRoute(user) {
        if (this.authController.isLoggedIn() &&
                user == this.authController.getCurrentUser().name) {
            this.topNavigationController.activate('account');
        } else {
            this.topNavigationController.activate('users');
        }
    }

    editUserRoute(user) {
        this.topNavigationController.activate('users');
    }
}

module.exports = UsersController;
