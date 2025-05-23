"use strict";

const api = require("../api.js");
const tags = require("../tags.js");
const misc = require("../util/misc.js");
const uri = require("../util/uri.js");
const Tag = require("../models/tag.js");
const settings = require("../models/settings.js");
const events = require("../events.js");
const views = require("../util/views.js");
const TagAutoCompleteControl = require("./tag_auto_complete_control.js");

const KEY_SPACE = 32;
const KEY_RETURN = 13;

const SOURCE_INIT = "init";
const SOURCE_IMPLICATION = "implication";
const SOURCE_USER_INPUT = "user-input";
const SOURCE_SUGGESTION = "suggestions";
const SOURCE_CLIPBOARD = "clipboard";

const template = views.getTemplate("tag-input");

function _fadeOutListItemNodeStatus(listItemNode) {
    if (listItemNode.classList.length) {
        if (listItemNode.fadeTimeout) {
            window.clearTimeout(listItemNode.fadeTimeout);
        }
        listItemNode.fadeTimeout = window.setTimeout(() => {
            while (listItemNode.classList.length) {
                listItemNode.classList.remove(listItemNode.classList.item(0));
            }
            listItemNode.fadeTimeout = null;
        }, 2500);
    }
}

class SuggestionList {
    constructor() {
        this._suggestions = {};
        this._banned = [];
    }

    clear() {
        this._suggestions = {};
    }

    get length() {
        return Object.keys(this._suggestions).length;
    }

    set(suggestion, weight) {
        if (
            Object.prototype.hasOwnProperty.call(this._suggestions, suggestion)
        ) {
            weight = Math.max(weight, this._suggestions[suggestion]);
        }
        this._suggestions[suggestion] = weight;
    }

    ban(suggestion) {
        this._banned.push(suggestion);
    }

    getAll() {
        let tuples = [];
        for (let suggestion of Object.keys(this._suggestions)) {
            if (!this._banned.includes(suggestion)) {
                const weight = this._suggestions[suggestion];
                tuples.push([suggestion, weight.toFixed(1)]);
            }
        }
        tuples.sort((a, b) => {
            let weightDiff = b[1] - a[1];
            let nameDiff = a[0].localeCompare(b[0]);
            return weightDiff === 0 ? nameDiff : weightDiff;
        });
        return tuples.map((tuple) => {
            return { tagName: tuple[0], weight: tuple[1] };
        });
    }
}

class TagInputControl extends events.EventTarget {
    constructor(hostNode, tagList) {
        super();
        this.tags = tagList;
        this._hostNode = hostNode;
        this._suggestions = new SuggestionList();
        this._tagToListItemNode = new Map();

        // dom
        const editAreaNode = template();
        this._editAreaNode = editAreaNode;
        this._tagInputNode = editAreaNode.querySelector("input");
        this._suggestionsNode = editAreaNode.querySelector(".tag-suggestions");
        this._tagListNode = editAreaNode.querySelector("ul.compact-tags");

        this._autoCompleteControl = new TagAutoCompleteControl(
            this._tagInputNode,
            {
                getTextToFind: () => {
                    return this._tagInputNode.value;
                },
                confirm: (tag) => {
                    this._tagInputNode.value = "";
                    // note: tags from autocomplete don't contain implications
                    // so they need to be looked up in API
                    this.addTagByName(tag.names[0], SOURCE_USER_INPUT);
                },
                delete: (tag) => {
                    this._tagInputNode.value = "";
                    this.deleteTag(tag);
                },
                verticalShift: -2,
                isTaggedWith: (tagName) => this.tags.isTaggedWith(tagName),
                isNegationAllowed: false,
            }
        );

        // dom events
        this._tagInputNode.addEventListener("keydown", (e) =>
            this._evtInputKeyDown(e)
        );
        this._tagInputNode.addEventListener("paste", (e) =>
            this._evtInputPaste(e)
        );
        this._editAreaNode
            .querySelector("a.opacity")
            .addEventListener("click", (e) =>
                this._evtToggleSuggestionsPopupOpacityClick(e)
            );
        this._editAreaNode
            .querySelector("a.close")
            .addEventListener("click", (e) =>
                this._evtCloseSuggestionsPopupClick(e)
            );
        this._editAreaNode
            .querySelector("button")
            .addEventListener("click", (e) => this._evtAddTagButtonClick(e));

        // show
        this._hostNode.style.display = "none";
        this._hostNode.parentNode.insertBefore(
            this._editAreaNode,
            hostNode.nextSibling
        );

        // add existing tags
        for (let tag of [...this.tags]) {
            const listItemNode = this._createListItemNode(tag);
            this._tagListNode.appendChild(listItemNode);
        }
    }

    addTagByText(text, source) {
        for (let tagName of text
            .split(/\s+/)
            .filter((word) => word)
            .reverse()) {
            this.addTagByName(tagName, source);
        }
    }

    addTagByName(name, source) {
        name = name.trim();
        // Tags `.` and `..` are not allowed, see https://github.com/rr-/szurubooru/pull/390
        if (!name || name == "." || name == "..") {
            return;
        }
        return Tag.get(name).then(
            (tag) => {
                return this.addTag(tag, source);
            },
            () => {
                const tag = new Tag();
                tag.names = [name];
                tag.category = null;
                return this.addTag(tag, source);
            }
        );
    }

    addTag(tag, source) {
        if (source !== SOURCE_INIT && this.tags.isTaggedWith(tag.names[0])) {
            const listItemNode = this._getListItemNode(tag);
            if (source !== SOURCE_IMPLICATION) {
                listItemNode.classList.add("duplicate");
                _fadeOutListItemNodeStatus(listItemNode);
            }
            return Promise.resolve();
        }

        return this.tags
            .addByName(tag.names[0], false)
            .then(() => {
                const listItemNode = this._createListItemNode(tag);
                if (!tag.category) {
                    listItemNode.classList.add("new");
                } else if (source === SOURCE_IMPLICATION) {
                    listItemNode.classList.add("implication");
                } else {
                    listItemNode.classList.add("added");
                }
                this._tagListNode.prependChild(listItemNode);
                _fadeOutListItemNodeStatus(listItemNode);

                return Promise.all(
                    tag.implications.map((implication) =>
                        this.addTagByName(
                            implication.names[0],
                            SOURCE_IMPLICATION
                        )
                    )
                );
            })
            .then(() => {
                this.dispatchEvent(
                    new CustomEvent("add", {
                        detail: { tag: tag, source: source },
                    })
                );
                this.dispatchEvent(new CustomEvent("change"));
                return Promise.resolve();
            });
    }

    deleteTag(tag) {
        if (!this.tags.isTaggedWith(tag.names[0])) {
            return;
        }
        this.tags.removeByName(tag.names[0]);
        this._hideAutoComplete();

        this._deleteListItemNode(tag);

        this.dispatchEvent(
            new CustomEvent("remove", {
                detail: { tag: tag },
            })
        );
        this.dispatchEvent(new CustomEvent("change"));
    }

    _evtInputPaste(e) {
        e.preventDefault();
        const pastedText = window.clipboardData
            ? window.clipboardData.getData("Text")
            : (e.originalEvent || e).clipboardData.getData("text/plain");

        if (pastedText.length > 2000) {
            window.alert("Pasted text is too long.");
            return;
        }
        this._hideAutoComplete();
        this.addTagByText(pastedText, SOURCE_CLIPBOARD);
        this._tagInputNode.value = "";
    }

    _evtCloseSuggestionsPopupClick(e) {
        e.preventDefault();
        this._closeSuggestionsPopup();
    }

    _evtAddTagButtonClick(e) {
        e.preventDefault();
        this.addTagByName(this._tagInputNode.value, SOURCE_USER_INPUT);
        this._tagInputNode.value = "";
    }

    _evtToggleSuggestionsPopupOpacityClick(e) {
        e.preventDefault();
        this._toggleSuggestionsPopupOpacity();
    }

    _evtInputKeyDown(e) {
        if (e.which === KEY_RETURN || e.which === KEY_SPACE) {
            e.preventDefault();
            this._hideAutoComplete();
            this.addTagByText(this._tagInputNode.value, SOURCE_USER_INPUT);
            this._tagInputNode.value = "";
        }
    }

    _createListItemNode(tag) {
        const className = tag.category
            ? misc.makeCssName(tag.category, "tag")
            : null;

        const tagLinkNode = document.createElement("a");
        if (className) {
            tagLinkNode.classList.add(className);
        }
        tagLinkNode.setAttribute(
            "href",
            uri.formatClientLink("tag", tag.names[0])
        );

        const tagIconNode = document.createElement("i");
        tagIconNode.classList.add("fa");
        tagIconNode.classList.add("fa-tag");
        tagLinkNode.appendChild(tagIconNode);

        const searchLinkNode = document.createElement("a");
        if (className) {
            searchLinkNode.classList.add(className);
        }
        searchLinkNode.setAttribute(
            "href",
            uri.formatClientLink("posts", {
                query: uri.escapeTagName(tag.names[0]),
            })
        );
        searchLinkNode.textContent = tag.names[0] + " ";
        searchLinkNode.addEventListener("click", (e) => {
            e.preventDefault();
            this._suggestions.clear();
            if (tag.postCount > 0) {
                this._loadSuggestions(tag);
                this._removeSuggestionsPopupOpacity();
            } else {
                this._closeSuggestionsPopup();
            }
        });

        const usagesNode = document.createElement("span");
        usagesNode.classList.add("tag-usages");
        usagesNode.setAttribute("data-pseudo-content", tag.postCount);

        const removalLinkNode = document.createElement("a");
        removalLinkNode.classList.add("remove-tag");
        removalLinkNode.setAttribute("href", "");
        removalLinkNode.setAttribute("data-pseudo-content", "×");
        removalLinkNode.addEventListener("click", (e) => {
            e.preventDefault();
            this.deleteTag(tag);
        });

        const listItemNode = document.createElement("li");
        listItemNode.appendChild(removalLinkNode);
        listItemNode.appendChild(tagLinkNode);
        listItemNode.appendChild(searchLinkNode);
        listItemNode.appendChild(usagesNode);
        for (let name of tag.names) {
            this._tagToListItemNode.set(name, listItemNode);
        }
        return listItemNode;
    }

    _deleteListItemNode(tag) {
        const listItemNode = this._getListItemNode(tag);
        if (listItemNode) {
            listItemNode.parentNode.removeChild(listItemNode);
        }
        for (let name of tag.names) {
            this._tagToListItemNode.delete(name);
        }
    }

    _getListItemNode(tag) {
        return this._tagToListItemNode.get(tag.names[0]);
    }

    _loadSuggestions(tag) {
        const browsingSettings = settings.get();
        if (!browsingSettings.tagSuggestions) {
            return;
        }
        api.get(uri.formatApiLink("tag-siblings", tag.names[0]), {
            noProgress: true,
        })
            .then(
                (response) => {
                    return Promise.resolve(response.results);
                },
                (response) => {
                    return Promise.resolve([]);
                }
            )
            .then((siblings) => {
                const args = siblings.map((s) => s.occurrences);
                let maxSiblingOccurrences = Math.max(1, ...args);
                for (let sibling of siblings) {
                    this._suggestions.set(
                        sibling.tag.names[0],
                        (sibling.occurrences * 4.9) / maxSiblingOccurrences
                    );
                }
                for (let suggestion of tag.suggestions || []) {
                    this._suggestions.set(suggestion, 5);
                }
                if (this._suggestions.length) {
                    this._openSuggestionsPopup();
                } else {
                    this._closeSuggestionsPopup();
                }
            });
    }

    _refreshSuggestionsPopup() {
        if (!this._suggestionsNode.classList.contains("shown")) {
            return;
        }
        const listNode = this._suggestionsNode.querySelector("ul");
        listNode.scrollTop = 0;
        while (listNode.firstChild) {
            listNode.removeChild(listNode.firstChild);
        }
        for (let tuple of this._suggestions.getAll()) {
            const tagName = tuple.tagName;
            const weight = tuple.weight;
            if (this.tags.isTaggedWith(tagName)) {
                continue;
            }

            const addLinkNode = document.createElement("a");
            addLinkNode.textContent = tagName;
            addLinkNode.classList.add("add-tag");
            addLinkNode.setAttribute("href", "");
            Tag.get(tagName).then((tag) => {
                addLinkNode.classList.add(
                    misc.makeCssName(tag.category, "tag")
                );
            });
            addLinkNode.addEventListener("click", (e) => {
                e.preventDefault();
                listNode.removeChild(listItemNode);
                this.addTagByName(tagName, SOURCE_SUGGESTION);
            });

            const weightNode = document.createElement("span");
            weightNode.classList.add("tag-weight");
            weightNode.setAttribute("data-pseudo-content", weight);

            const removalLinkNode = document.createElement("a");
            removalLinkNode.classList.add("remove-tag");
            removalLinkNode.setAttribute("href", "");
            removalLinkNode.setAttribute("data-pseudo-content", "×");
            removalLinkNode.addEventListener("click", (e) => {
                e.preventDefault();
                listNode.removeChild(listItemNode);
                this._suggestions.ban(tagName);
            });

            const listItemNode = document.createElement("li");
            listItemNode.appendChild(removalLinkNode);
            listItemNode.appendChild(weightNode);
            listItemNode.appendChild(addLinkNode);
            listNode.appendChild(listItemNode);
        }
    }

    _closeSuggestionsPopup() {
        this._suggestions.clear();
        this._suggestionsNode.classList.remove("shown");
    }

    _removeSuggestionsPopupOpacity() {
        this._suggestionsNode.classList.remove("translucent");
    }

    _toggleSuggestionsPopupOpacity() {
        this._suggestionsNode.classList.toggle("translucent");
    }

    _openSuggestionsPopup() {
        this._suggestionsNode.classList.add("shown");
        this._refreshSuggestionsPopup();
    }

    _hideAutoComplete() {
        this._autoCompleteControl.hide();
    }
}

module.exports = TagInputControl;
