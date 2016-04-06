'use strict';

const handlebars = require('handlebars');

// fix iterating over NodeList in Chrome and Opera
NodeList.prototype[Symbol.iterator] = Array.prototype[Symbol.iterator];

class BaseView {
    constructor() {
        this.contentHolder = document.getElementById('content-holder');
    }

    getTemplate(templatePath) {
        const templateElement = document.getElementById(templatePath);
        if (!templateElement) {
            console.log('Missing template: ' + templatePath);
            return null;
        }
        const templateText = templateElement.innerHTML;
        return handlebars.compile(templateText);
    }

    notifyError(message) {
        this.notify(message, 'error');
    }

    notifySuccess(message) {
        this.notify(message, 'success');
    }

    notify(message, className) {
        const messagesHolder = this.contentHolder.querySelector('.messages');
        /* TODO: animate this */
        const node = document.createElement('div');
        node.innerHTML = message.replace(/\n/g, '<br/>');
        node.classList.add('message');
        node.classList.add(className);
        messagesHolder.appendChild(node);
    }

    clearMessages() {
        const messagesHolder = this.contentHolder.querySelector('.messages');
        /* TODO: animate that */
        while (messagesHolder.lastChild) {
            messagesHolder.removeChild(messagesHolder.lastChild);
        }
    }

    decorateValidator(form) {
        // postpone showing form fields validity until user actually tries
        // to submit it (seeing red/green form w/o doing anything breaks POLA)
        const submitButton = form.querySelector('.buttons input');
        submitButton.addEventListener('click', e => {
            form.classList.add('show-validation');
        });
        form.addEventListener('submit', e => {
            form.classList.remove('show-validation');
        });
    }

    disableForm(form) {
        for (let input of form.querySelectorAll('input')) {
            input.disabled = true;
        }
    }

    enableForm(form) {
        for (let input of form.querySelectorAll('input')) {
            input.disabled = false;
        }
    }

    showView(html) {
        this.contentHolder.innerHTML = html;
    }
}

module.exports = BaseView;
