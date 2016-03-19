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

    decorateValidator(form) {
        // postpone showing form fields validity until user actually tries
        // to submit it (seeing red/green form w/o doing anything breaks POLA)
        const submitButton
            = document.querySelector('#content-holder .buttons input');
        submitButton.addEventListener('click', (e) => {
            form.classList.add('show-validation');
        });
    }

    showView(html) {
        this.contentHolder.innerHTML = html;
    }
}

module.exports = BaseView;
