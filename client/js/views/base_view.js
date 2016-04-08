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
        this.domParser = new DOMParser();
    }

    htmlToDom(html) {
        const parsed = this.domParser.parseFromString(html, 'text/html').body;
        return parsed.childNodes.length > 1 ?
            parsed.childNodes :
            parsed.firstChild;
    }

    getTemplate(templatePath) {
        const templateElement = document.getElementById(templatePath);
        if (!templateElement) {
            console.error('Missing template: ' + templatePath);
            return null;
        }
        const templateText = templateElement.innerHTML.trim();
        const templateFactory = handlebars.compile(templateText);
        return (...args) => {
            return this.htmlToDom(templateFactory(...args));
        };
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

    emptyView(target) {
        return this.showView(
            target,
            this.htmlToDom('<div class="messages"></div>'));
    }

    showView(target, source) {
        return new Promise((resolve, reject) => {
            let observer = new MutationObserver(mutations => {
                resolve();
                observer.disconnect();
            });
            observer.observe(target, {childList: true});
            while (target.lastChild) {
                target.removeChild(target.lastChild);
            }
            if (source instanceof NodeList) {
                for (let child of source) {
                    target.appendChild(child);
                }
            } else if (source instanceof Node) {
                target.appendChild(source);
            } else {
                console.error('Invalid view source', source);
            }
        });
    }
}

module.exports = BaseView;
