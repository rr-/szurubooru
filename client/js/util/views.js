'use strict';

require('../util/polyfill.js');
const handlebars = require('handlebars');
const events = require('../events.js');
const domParser = new DOMParser();

function _messageHandler(target, message, className) {
    if (!message) {
        message = 'Unknown message';
    }
    const messagesHolder = target.querySelector('.messages');
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

function _serializeElement(name, attributes) {
    return [name]
        .concat(Object.keys(attributes).map(key => {
            if (attributes[key] === true) {
                return key;
            } else if (attributes[key] === false ||
                    attributes[key] === undefined) {
                return '';
            }
            return '{0}="{1}"'.format(key, attributes[key]);
        }))
        .join(' ');
}

function makeNonVoidElement(name, attributes, content) {
    return '<{0}>{1}</{2}>'.format(
        _serializeElement(name, attributes), content, name);
}

function makeVoidElement(name, attributes) {
    return '<{0}/>'.format(_serializeElement(name, attributes));
}

function listenToMessages(target) {
    events.unlisten(events.Success);
    events.unlisten(events.Error);
    events.listen(
        events.Success, msg => { _messageHandler(target, msg, 'success'); });
    events.listen(
        events.Error, msg => { _messageHandler(target, msg, 'error'); });
}

function clearMessages(target) {
    const messagesHolder = target.querySelector('.messages');
    /* TODO: animate that */
    while (messagesHolder.lastChild) {
        messagesHolder.removeChild(messagesHolder.lastChild);
    }
}

function htmlToDom(html) {
    const parsed = domParser.parseFromString(html, 'text/html').body;
    return parsed.childNodes.length > 1 ?
        parsed.childNodes :
        parsed.firstChild;
}

function getTemplate(templatePath) {
    const templateElement = document.getElementById(templatePath + '-template');
    if (!templateElement) {
        console.error('Missing template: ' + templatePath);
        return null;
    }
    const templateText = templateElement.innerHTML.trim();
    const templateFactory = handlebars.compile(templateText);
    return (...args) => {
        return htmlToDom(templateFactory(...args));
    };
}

function decorateValidator(form) {
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

function disableForm(form) {
    for (let input of form.querySelectorAll('input')) {
        input.disabled = true;
    }
}

function enableForm(form) {
    for (let input of form.querySelectorAll('input')) {
        input.disabled = false;
    }
}

function showView(target, source) {
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

module.exports = {
    htmlToDom: htmlToDom,
    getTemplate: getTemplate,
    showView: showView,
    enableForm: enableForm,
    disableForm: disableForm,
    listenToMessages: listenToMessages,
    clearMessages: clearMessages,
    decorateValidator: decorateValidator,
    makeVoidElement: makeVoidElement,
    makeNonVoidElement: makeNonVoidElement,
};
