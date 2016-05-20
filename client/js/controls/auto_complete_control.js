'use strict';

const lodash = require('lodash');
const views = require('../util/views.js');

const KEY_TAB = 9;
const KEY_RETURN = 13;
const KEY_DELETE = 46;
const KEY_ESCAPE = 27;
const KEY_UP = 38;
const KEY_DOWN = 40;

function _getSelectionStart(input) {
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
    constructor(sourceInputNode, options) {
        this._sourceInputNode = sourceInputNode;
        this._options = lodash.extend({}, {
            verticalShift: 2,
            source: null,
            maxResults: 15,
            getTextToFind: () => {
                const value = sourceInputNode.value;
                const start = _getSelectionStart(sourceInputNode);
                return value.substring(0, start).replace(/.*\s+/, '');
            },
            confirm: text => {
                const start = _getSelectionStart(sourceInputNode);
                let prefix = '';
                let suffix = sourceInputNode.value.substring(start);
                let middle = sourceInputNode.value.substring(0, start);
                const index = middle.lastIndexOf(' ');
                if (index !== -1) {
                    prefix = sourceInputNode.value.substring(0, index + 1);
                    middle = sourceInputNode.value.substring(index + 1);
                }
                sourceInputNode.value = prefix +
                    this._results[this._activeResult].value +
                    ' ' +
                    suffix.trimLeft();
                sourceInputNode.focus();
            },
            delete: text => {
            },
            getMatches: null,
        }, options);

        this._showTimeout = null;
        this._results = [];
        this._activeResult = -1;

        this._mutationObserver = new MutationObserver(
            mutations => {
                for (let mutation of mutations) {
                    for (let node of mutation.removedNodes) {
                        if (node.contains(this._sourceInputNode)) {
                            this._uninstall();
                            return;
                        }
                    }
                }
            });

        this._install();
    }

    hide() {
        window.clearTimeout(this._showTimeout);
        this._suggestionDiv.style.display = 'none';
        this._isVisible = false;
    }

    _show() {
        this._suggestionDiv.style.display = 'block';
        this._isVisible = true;
    }

    _showOrHide() {
        const textToFind = this._options.getTextToFind();
        if (!textToFind || !textToFind.length) {
            this.hide();
        } else {
            this._updateResults(textToFind);
            this._refreshList();
        }
    }

    _install() {
        if (!this._sourceInputNode) {
            throw new Error('Input element was not found');
        }
        if (this._sourceInputNode.getAttribute('data-autocomplete')) {
            throw new Error(
                'Autocompletion was already added for this element');
        }
        this._sourceInputNode.setAttribute('data-autocomplete', true);
        this._sourceInputNode.setAttribute('autocomplete', 'off');

        this._mutationObserver.observe(
            document.body, {childList: true, subtree: true});
        this._sourceInputNode.addEventListener(
            'keydown', e => this._evtKeyDown(e));
        this._sourceInputNode.addEventListener(
            'blur', e => this._evtBlur(e));

        this._suggestionDiv = views.htmlToDom(
            '<div class="autocomplete"><ul></ul></div>');
        this._suggestionList = this._suggestionDiv.querySelector('ul');
        document.body.appendChild(this._suggestionDiv);
    }

    _uninstall() {
        window.clearTimeout(this._showTimeout);
        this._mutationObserver.disconnect();
        document.body.removeChild(this._suggestionDiv);
    }

    _evtKeyDown(e) {
        const key = e.which;
        const shift = e.shiftKey;
        let func = null;
        if (this._isVisible) {
            if (key === KEY_ESCAPE) {
                func = this.hide;
            } else if (key === KEY_TAB && shift) {
                func = () => { this._selectPrevious(); };
            } else if (key === KEY_TAB && !shift) {
                func = () => { this._selectNext(); };
            } else if (key === KEY_UP) {
                func = () => { this._selectPrevious(); };
            } else if (key === KEY_DOWN) {
                func = () => { this._selectNext(); };
            } else if (key === KEY_RETURN && this._activeResult >= 0) {
                func = () => {
                    this._options.confirm(this._getActiveSuggestion());
                    this.hide();
                };
            } else if (key === KEY_DELETE && this._activeResult >= 0) {
                func = () => {
                    this._options.delete(this._getActiveSuggestion());
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
            window.clearTimeout(this._showTimeout);
            this._showTimeout = window.setTimeout(
                () => { this._showOrHide(); }, 250);
        }
    }

    _evtBlur(e) {
        window.clearTimeout(this._showTimeout);
        window.setTimeout(() => { this.hide(); }, 50);
    }

    _getActiveSuggestion() {
        if (this._activeResult === -1) {
            return null;
        }
        return this._results[this._activeResult].value;
    }

    _selectPrevious() {
        this._select(this._activeResult === -1 ?
            this._results.length - 1 :
            this._activeResult - 1);
    }

    _selectNext() {
        this._select(this._activeResult === -1 ? 0 : this._activeResult + 1);
    }

    _select(newActiveResult) {
        this._activeResult =
            newActiveResult.between(0, this._results.length - 1, true) ?
                newActiveResult :
                -1;
        this._refreshActiveResult();
    }

    _updateResults(textToFind) {
        const oldResults = this._results.slice();
        this._results =
            this._options.getMatches(textToFind)
            .slice(0, this._options.maxResults);
        if (!lodash.isEqual(oldResults, this._results)) {
            this._activeResult = -1;
        }
    }

    _refreshList() {
        if (this._results.length === 0) {
            this.hide();
            return;
        }

        while (this._suggestionList.firstChild) {
            this._suggestionList.removeChild(this._suggestionList.firstChild);
        }
        lodash.each(
            this._results,
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
                        this._activeResult = resultIndex;
                        this._refreshActiveResult();
                    });
                link.addEventListener(
                    'mousedown',
                    e => {
                        e.preventDefault();
                        this._activeResult = resultIndex;
                        this._options.confirm(this._getActiveSuggestion());
                        this.hide();
                    });
                listItem.appendChild(link);
                this._suggestionList.appendChild(listItem);
            });
        this._refreshActiveResult();

        // display the suggestions offscreen to get the height
        this._suggestionDiv.style.left = '-9999px';
        this._suggestionDiv.style.top = '-9999px';
        this._show();
        const verticalShift = this._options.verticalShift;
        const inputRect = this._sourceInputNode.getBoundingClientRect();
        const bodyRect = document.body.getBoundingClientRect();
        const viewPortHeight = bodyRect.bottom - bodyRect.top;
        let listRect = this._suggestionDiv.getBoundingClientRect();

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
                this._suggestionList.childNodes.length) {
            this._suggestionList.removeChild(this._suggestionList.lastChild);
            const prevHeight = listRect.height;
            listRect = this._suggestionDiv.getBoundingClientRect();
            const heightDelta = prevHeight - listRect.height;
            if (direction == -1) {
                y += heightDelta;
            }
        }

        this._suggestionDiv.style.left = x + 'px';
        this._suggestionDiv.style.top = y + 'px';
    }

    _refreshActiveResult() {
        let activeItem = this._suggestionList.querySelector('li.active');
        if (activeItem) {
            activeItem.classList.remove('active');
        }
        if (this._activeResult >= 0) {
            const allItems = this._suggestionList.querySelectorAll('li');
            activeItem = allItems[this._activeResult];
            activeItem.classList.add('active');
        }
    }
};

module.exports = AutoCompleteControl;
