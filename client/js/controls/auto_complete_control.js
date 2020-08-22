"use strict";

const views = require("../util/views.js");

const KEY_TAB = 9;
const KEY_RETURN = 13;
const KEY_DELETE = 46;
const KEY_ESCAPE = 27;
const KEY_UP = 38;
const KEY_DOWN = 40;

function _getSelectionStart(input) {
    if ("selectionStart" in input) {
        return input.selectionStart;
    }
    if (document.selection) {
        input.focus();
        const sel = document.selection.createRange();
        const selLen = document.selection.createRange().text.length;
        sel.moveStart("character", -input.value.length);
        return sel.text.length - selLen;
    }
    return 0;
}

class AutoCompleteControl {
    constructor(sourceInputNode, options) {
        this._sourceInputNode = sourceInputNode;
        this._options = {};
        Object.assign(
            this._options,
            {
                verticalShift: 2,
                maxResults: 15,
                getTextToFind: () => {
                    const value = sourceInputNode.value;
                    const start = _getSelectionStart(sourceInputNode);
                    return value.substring(0, start).replace(/.*\s+/, "");
                },
                confirm: null,
                delete: null,
                getMatches: null,
            },
            options
        );

        this._showTimeout = null;
        this._results = [];
        this._activeResult = -1;

        this._install();
    }

    hide() {
        window.clearTimeout(this._showTimeout);
        this._suggestionDiv.style.display = "none";
        this._isVisible = false;
    }

    replaceSelectedText(result, addSpace) {
        const start = _getSelectionStart(this._sourceInputNode);
        let prefix = "";
        let suffix = this._sourceInputNode.value.substring(start);
        let middle = this._sourceInputNode.value.substring(0, start);
        const spaceIndex = middle.lastIndexOf(" ");
        const commaIndex = middle.lastIndexOf(",");
        const index = spaceIndex < commaIndex ? commaIndex : spaceIndex;
        const delimiter = spaceIndex < commaIndex ? "" : " ";
        if (index !== -1) {
            prefix = this._sourceInputNode.value.substring(0, index + 1);
            middle = this._sourceInputNode.value.substring(index + 1);
        }
        this._sourceInputNode.value =
            prefix + result.toString() + delimiter + suffix.trimLeft();
        if (!addSpace) {
            this._sourceInputNode.value = this._sourceInputNode.value.trim();
        }
        this._sourceInputNode.focus();
    }

    _delete(result) {
        if (this._options.delete) {
            this._options.delete(result);
        }
    }

    _confirm(result) {
        if (this._options.confirm) {
            this._options.confirm(result);
        } else {
            this.defaultConfirmStrategy(result);
        }
    }

    _show() {
        this._suggestionDiv.style.display = "block";
        this._isVisible = true;
    }

    _showOrHide() {
        const textToFind = this._options.getTextToFind();
        if (!textToFind || !textToFind.length) {
            this.hide();
        } else {
            this._updateResults(textToFind);
        }
    }

    _install() {
        if (!this._sourceInputNode) {
            throw new Error("Input element was not found");
        }
        if (this._sourceInputNode.getAttribute("data-autocomplete")) {
            throw new Error(
                "Autocompletion was already added for this element"
            );
        }
        this._sourceInputNode.setAttribute("data-autocomplete", true);
        this._sourceInputNode.setAttribute("autocomplete", "off");

        this._sourceInputNode.addEventListener("keydown", (e) =>
            this._evtKeyDown(e)
        );
        this._sourceInputNode.addEventListener("blur", (e) =>
            this._evtBlur(e)
        );

        this._suggestionDiv = views.htmlToDom(
            '<div class="autocomplete"><ul></ul></div>'
        );
        this._suggestionList = this._suggestionDiv.querySelector("ul");
        document.body.appendChild(this._suggestionDiv);

        views.monitorNodeRemoval(this._sourceInputNode, () => {
            this._uninstall();
        });
    }

    _uninstall() {
        window.clearTimeout(this._showTimeout);
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
                func = () => {
                    this._selectPrevious();
                };
            } else if (key === KEY_TAB && !shift) {
                func = () => {
                    this._selectNext();
                };
            } else if (key === KEY_UP) {
                func = () => {
                    this._selectPrevious();
                };
            } else if (key === KEY_DOWN) {
                func = () => {
                    this._selectNext();
                };
            } else if (key === KEY_RETURN && this._activeResult >= 0) {
                func = () => {
                    this._confirm(this._getActiveSuggestion());
                    this.hide();
                };
            } else if (key === KEY_DELETE && this._activeResult >= 0) {
                func = () => {
                    this._delete(this._getActiveSuggestion());
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
            this._showTimeout = window.setTimeout(() => {
                this._showOrHide();
            }, 250);
        }
    }

    _evtBlur(e) {
        window.clearTimeout(this._showTimeout);
        window.setTimeout(() => {
            this.hide();
        }, 50);
    }

    _getActiveSuggestion() {
        if (this._activeResult === -1) {
            return null;
        }
        return this._results[this._activeResult].value;
    }

    _selectPrevious() {
        this._select(
            this._activeResult === -1
                ? this._results.length - 1
                : this._activeResult - 1
        );
    }

    _selectNext() {
        this._select(this._activeResult === -1 ? 0 : this._activeResult + 1);
    }

    _select(newActiveResult) {
        this._activeResult = newActiveResult.between(
            0,
            this._results.length - 1,
            true
        )
            ? newActiveResult
            : -1;
        this._refreshActiveResult();
    }

    _updateResults(textToFind) {
        this._options.getMatches(textToFind).then((matches) => {
            const oldResults = this._results.slice();
            this._results = matches.slice(0, this._options.maxResults);
            const oldResultsHash = JSON.stringify(oldResults);
            const newResultsHash = JSON.stringify(this._results);
            if (oldResultsHash !== newResultsHash) {
                this._activeResult = -1;
            }
            this._refreshList();
        });
    }

    _refreshList() {
        if (this._results.length === 0) {
            this.hide();
            return;
        }

        while (this._suggestionList.firstChild) {
            this._suggestionList.removeChild(this._suggestionList.firstChild);
        }
        for (let [resultIndex, resultItem] of this._results.entries()) {
            let resultIndexWorkaround = resultIndex;
            const listItem = document.createElement("li");
            const link = document.createElement("a");
            link.innerHTML = resultItem.caption;
            link.setAttribute("href", "");
            link.setAttribute("data-key", resultItem.value);
            link.addEventListener("mouseenter", (e) => {
                e.preventDefault();
                this._activeResult = resultIndexWorkaround;
                this._refreshActiveResult();
            });
            link.addEventListener("mousedown", (e) => {
                e.preventDefault();
                this._activeResult = resultIndexWorkaround;
                this._confirm(this._getActiveSuggestion());
                this.hide();
            });
            listItem.appendChild(link);
            this._suggestionList.appendChild(listItem);
        }
        this._refreshActiveResult();

        // display the suggestions offscreen to get the height
        this._suggestionDiv.style.left = "-9999px";
        this._suggestionDiv.style.top = "-9999px";
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
        let y =
            direction === 1
                ? inputRect.bottom - bodyRect.top - verticalShift
                : inputRect.top -
                  bodyRect.top -
                  listRect.height +
                  verticalShift;

        // remove offscreen items until whole suggestion list can fit on the
        // screen
        while (
            (y < 0 || y + listRect.height > viewPortHeight) &&
            this._suggestionList.childNodes.length
        ) {
            this._suggestionList.removeChild(this._suggestionList.lastChild);
            const prevHeight = listRect.height;
            listRect = this._suggestionDiv.getBoundingClientRect();
            const heightDelta = prevHeight - listRect.height;
            if (direction === -1) {
                y += heightDelta;
            }
        }

        this._suggestionDiv.style.left = x + "px";
        this._suggestionDiv.style.top = y + "px";
    }

    _refreshActiveResult() {
        let activeItem = this._suggestionList.querySelector("li.active");
        if (activeItem) {
            activeItem.classList.remove("active");
        }
        if (this._activeResult >= 0) {
            const allItems = this._suggestionList.querySelectorAll("li");
            activeItem = allItems[this._activeResult];
            activeItem.classList.add("active");
        }
    }
}

module.exports = AutoCompleteControl;
