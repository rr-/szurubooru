'use strict';

const handlebars = require('handlebars');
const events = require('../events.js');
const contentHolder = document.getElementById('content-holder');
require('../util/polyfill.js');

function messageHandler(message, className) {
    if (!message) {
        message = 'Unknown message';
    }
    const messagesHolder = contentHolder.querySelector('.messages');
    if (!messagesHolder) {
        alert(message);
        return;
    }
    /* TODO: animate this */
    const node = document.createElement('div');
    node.innerHTML = message.replace(/\n/g, '<br/>');
    node.classList.add('message');
    node.classList.add(className);
    messagesHolder.appendChild(node);
}

events.listen(events.Success, msg => { messageHandler(msg, 'success'); });
events.listen(events.Error, msg => { messageHandler(msg, 'error'); });

class BaseView {
    constructor() {
        this.contentHolder = contentHolder;
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

    empty() {
        this.showView('<div class="messages"></div>');
    }

    showView(html) {
        this.contentHolder.innerHTML = html;
    }
}

module.exports = BaseView;
