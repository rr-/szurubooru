'use strict';

// fix iterating over NodeList in Chrome and Opera
NodeList.prototype[Symbol.iterator] = Array.prototype[Symbol.iterator];

class BaseView {
    constructor(handlebars) {
        this.handlebars = handlebars;
        this.contentHolder = document.getElementById('content-holder');
    }

    getTemplate(templatePath) {
        const templateElement = document.getElementById(templatePath);
        if (!templateElement) {
            console.log('Missing template: ' + templatePath);
            return null;
        }
        const templateText = templateElement.innerHTML;
        return this.handlebars.compile(templateText);
    }

    showError(messagesHolder, errorMessage) {
        /* TODO: animate this */
        const node = document.createElement('div');
        node.innerHTML = errorMessage;
        node.classList.add('message');
        node.classList.add('error');
        messagesHolder.appendChild(node);
    }

    clearMessages(messagesHolder) {
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

    showView(html) {
        this.contentHolder.innerHTML = html;
    }
}

module.exports = BaseView;
