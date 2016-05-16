'use strict';

const lodash = require('lodash');
const views = require('../util/views.js');

const KEY_TAB = 9;
const KEY_RETURN = 13;
const KEY_DELETE = 46;
const KEY_ESCAPE = 27;
const KEY_UP = 38;
const KEY_DOWN = 40;

function getSelectionStart(input) {
    if ('selectionStart' in input) {
        return input.selectionStart;
    }
    if (document.selection) {
        input.focus();
        const sel = document.selection.createRange();
        const selLen = document.selection.createRange().text.length;
        sel.moveStart('character', -input.value.length);
        return sel.text.length - selLen;
    }
    return 0;
}

class AutoCompleteControl {
    constructor(input, options) {
        this.input = input;
        this.options = lodash.extend({}, {
            verticalShift: 2,
            source: null,
            maxResults: 15,
            getTextToFind: () => {
                const value = this.input.value;
                const start = getSelectionStart(this.input);
                return value.substring(0, start).replace(/.*\s+/, '');
            },
            confirm: text => {
                const start = getSelectionStart(this.input);
                let prefix = '';
                let suffix = this.input.value.substring(start);
                let middle = this.input.value.substring(0, start);
                const index = middle.lastIndexOf(' ');
                if (index !== -1) {
                    prefix = this.input.value.substring(0, index + 1);
                    middle = this.input.value.substring(index + 1);
                }
                this.input.value = prefix +
                    this.results[this.activeResult].value +
                    ' ' +
                    suffix.trimLeft();
                this.input.focus();
            },
            delete: text => {
            },
            getMatches: null,
        }, options);

        this.showTimeout = null;
        this.results = [];
        this.activeResult = -1;

        this.install();
    }

    install() {
        if (!this.input) {
            throw new Error('Input element was not found');
        }
        if (this.input.getAttribute('data-autocomplete')) {
            throw new Error(
                'Autocompletion was already added for this element');
        }
        this.input.setAttribute('data-autocomplete', true);
        this.input.setAttribute('autocomplete', 'off');

        this.input.addEventListener(
            'keydown',
            e => {
                const key = e.which;
                const shift = e.shiftKey;
                let func = null;
                if (this.isVisible) {
                    if (key === KEY_ESCAPE) {
                        func = this.hide;
                    } else if (key === KEY_TAB && shift) {
                        func = () => { this.selectPrevious(); };
                    } else if (key === KEY_TAB && !shift) {
                        func = () => { this.selectNext(); };
                    } else if (key === KEY_UP) {
                        func = () => { this.selectPrevious(); };
                    } else if (key === KEY_DOWN) {
                        func = () => { this.selectNext(); };
                    } else if (key === KEY_RETURN && this.activeResult >= 0) {
                        func = () => {
                            this.options.confirm(this.getActiveSuggestion());
                            this.hide();
                        };
                    } else if (key === KEY_DELETE && this.activeResult >= 0) {
                        func = () => {
                            this.options.delete(this.getActiveSuggestion());
                            this.hide();
                        };
                    }
                }

                if (func !== null) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    func();
                } else {
                    window.clearTimeout(this.showTimeout);
                    this.showTimeout = window.setTimeout(
                        () => { this.showOrHide(); },
                        250);
                }
            });

        this.input.addEventListener(
            'blur',
            e => {
                window.clearTimeout(this.showTimeout);
                window.setTimeout(() => { this.hide(); }, 50);
            });

        this.suggestionDiv = views.htmlToDom(
            '<div class="autocomplete"><ul></ul></div>');
        this.suggestionList = this.suggestionDiv.querySelector('ul');
        document.body.appendChild(this.suggestionDiv);
    }

    getActiveSuggestion() {
        if (this.activeResult === -1) {
            return null;
        }
        return this.results[this.activeResult].value;
    }

    showOrHide() {
        const textToFind = this.options.getTextToFind();
        if (!textToFind || !textToFind.length) {
            this.hide();
        } else {
            this.updateResults(textToFind);
            this.refreshList();
        }
    }

    show() {
        this.suggestionDiv.style.display = 'block';
        this.isVisible = true;
    }

    hide() {
        if (this.showTimeout) {
            window.clearTimeout(this.showTimeout);
        }
        this.suggestionDiv.style.display = 'none';
        this.isVisible = false;
    }

    selectPrevious() {
        this.select(this.activeResult === -1 ?
            this.results.length - 1 :
            this.activeResult - 1);
    }

    selectNext() {
        this.select(this.activeResult === -1 ? 0 : this.activeResult + 1);
    }

    select(newActiveResult) {
        this.activeResult =
            newActiveResult.between(0, this.results.length - 1, true) ?
                newActiveResult :
                -1;
        this.refreshActiveResult();
    }

    updateResults(textToFind) {
        const oldResults = this.results.slice();
        this.results =
            this.options.getMatches(textToFind)
            .slice(0, this.options.maxResults);
        if (!lodash.isEqual(oldResults, this.results)) {
            this.activeResult = -1;
        }
    }

    refreshList() {
        if (this.results.length === 0) {
            this.hide();
            return;
        }

        while (this.suggestionList.firstChild) {
            this.suggestionList.removeChild(this.suggestionList.firstChild);
        }
        lodash.each(
            this.results,
            (resultItem, resultIndex) => {
                const listItem = document.createElement('li');
                const link = document.createElement('a');
                link.href = '#';
                link.innerHTML = resultItem.caption;
                link.setAttribute('data-key', resultItem.value);
                link.addEventListener(
                    'mouseenter',
                    e => {
                        e.preventDefault();
                        this.activeResult = resultIndex;
                        this.refreshActiveResult();
                    });
                link.addEventListener(
                    'mousedown',
                    e => {
                        e.preventDefault();
                        this.activeResult = resultIndex;
                        this.options.confirm(this.getActiveSuggestion());
                        this.hide();
                    });
                listItem.appendChild(link);
                this.suggestionList.appendChild(listItem);
            });
        this.refreshActiveResult();

        // display the suggestions offscreen to get the height
        this.suggestionDiv.style.left = '-9999px';
        this.suggestionDiv.style.top = '-9999px';
        this.show();
        const verticalShift = this.options.verticalShift;
        const inputRect = this.input.getBoundingClientRect();
        const bodyRect = document.body.getBoundingClientRect();
        const viewPortHeight = bodyRect.bottom - bodyRect.top;
        let listRect = this.suggestionDiv.getBoundingClientRect();

        // choose where to view the suggestions: if there's more space above
        // the input - draw the suggestions above it, otherwise below
        const direction =
            inputRect.top + inputRect.height / 2 < viewPortHeight / 2 ? 1 : -1;

        let x = inputRect.left - bodyRect.left;
        let y = direction == 1 ?
            inputRect.bottom - bodyRect.top - verticalShift :
            inputRect.top - bodyRect.top - listRect.height + verticalShift;

        // remove offscreen items until whole suggestion list can fit on the
        // screen
        while ((y < 0 || y + listRect.height > viewPortHeight) &&
                this.suggestionList.childNodes.length) {
            this.suggestionList.removeChild(this.suggestionList.lastChild);
            const prevHeight = listRect.height;
            listRect = this.suggestionDiv.getBoundingClientRect();
            const heightDelta = prevHeight - listRect.height;
            if (direction == -1) {
                y += heightDelta;
            }
        }

        this.suggestionDiv.style.left = x + 'px';
        this.suggestionDiv.style.top = y + 'px';
    }

    refreshActiveResult() {
        let activeItem = this.suggestionList.querySelector('li.active');
        if (activeItem) {
            activeItem.classList.remove('active');
        }
        if (this.activeResult >= 0) {
            const allItems = this.suggestionList.querySelectorAll('li');
            activeItem = allItems[this.activeResult];
            activeItem.classList.add('active');
        }
    }
};

module.exports = AutoCompleteControl;
