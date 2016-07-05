'use strict';

const api = require('../api.js');
const tags = require('../tags.js');
const misc = require('../util/misc.js');
const views = require('../util/views.js');
const TagAutoCompleteControl = require('./tag_auto_complete_control.js');

const KEY_A = 65;
const KEY_END = 35;
const KEY_HOME = 36;
const KEY_LEFT = 37;
const KEY_RIGHT = 39;
const KEY_SPACE = 32;
const KEY_RETURN = 13;
const KEY_BACKSPACE = 8;
const KEY_DELETE = 46;

class TagInputControl {
    constructor(sourceInputNode) {
        this.tags = [];
        this.readOnly = sourceInputNode.readOnly;

        this._relationsTemplate = views.getTemplate('tag-relations');
        this._relationsNodes = [];
        this._autoCompleteControls = [];
        this._sourceInputNode = sourceInputNode;

        this._install();
    }

    _install() {
        // set up main edit area
        this._editAreaNode = views.htmlToDom('<div class="tag-input"></div>');
        this._editAreaNode.autocorrect = false;
        this._editAreaNode.autocapitalize = false;
        this._editAreaNode.spellcheck = false;
        this._editAreaNode.addEventListener(
            'click', e => this._evtEditAreaClick(e));

        // set up tail editor
        this._tailWrapperNode = this._createWrapper();
        if (!this.readOnly) {
            this._tailInputNode = this._createInput();
            this._tailInputNode.tabIndex = 0;
            this._tailWrapperNode.appendChild(this._tailInputNode);
        } else {
            this._tailInputNode = null;
        }
        this._editAreaNode.appendChild(this._tailWrapperNode);

        // add existing tags
        this.addMultipleTags(this._sourceInputNode.value, false);

        // show
        this._sourceInputNode.style.display = 'none';
        this._sourceInputNode.parentNode.insertBefore(
            this._editAreaNode, this._sourceInputNode.nextSibling);
    }

    addMultipleTags(text, sourceNode, addImplications) {
        for (let tag of text.split(/\s+/).filter(word => word)) {
            this.addTag(tag, sourceNode, addImplications, false);
        }
    }

    isTaggedWith(tag) {
        return this.tags
            .map(t => t.toLowerCase())
            .includes(tag.toLowerCase());
    }

    addTag(text, sourceNode, addImplications, suggestRelations) {
        text = tags.getOriginalTagName(text);

        if (!sourceNode) {
            sourceNode = this._tailWrapperNode;
        }

        if (!text) {
            return;
        }

        if (this.tags.map(tag => tag.toLowerCase())
                .includes(text.toLowerCase())) {
            this._getWrapperFromTag(text).classList.add('duplicate');
            return;
        }

        this._hideVisualCues();

        this.tags.push(text);
        this._sourceInputNode.value = this.tags.join(' ');

        const sourceWrapperNode = this._getWrapperFromChild(sourceNode);
        const targetWrapperNode = this._createWrapper();
        if (!this.readOnly) {
            targetWrapperNode.appendChild(this._createInput());
        }
        if (!tags.getTagByName(text)) {
            targetWrapperNode.classList.add('new');
        }
        targetWrapperNode.appendChild(this._createLink(text));
        targetWrapperNode.setAttribute('data-tag', text);
        this._editAreaNode.insertBefore(targetWrapperNode, sourceWrapperNode);
        this._editAreaNode.insertBefore(this._createSpace(), sourceWrapperNode);

        // XXX: perhaps we should aggregate suggestions from all implications
        // for call to the _suggestRelations
        if (addImplications) {
            for (let otherTag of tags.getAllImplications(text)) {
                this.addTag(otherTag, sourceNode, true, false);
            }
        }

        if (suggestRelations) {
            this._suggestRelations([], tags.getSuggestions(text) || []);
        }
    }

    deleteTag(tag) {
        if (!tag) {
            return;
        }
        if (!this.tags.map(tag => tag.toLowerCase())
                .includes(tag.toLowerCase())) {
            return;
        }
        this._hideAutoComplete();
        this.tags = this.tags.filter(t => t.toLowerCase() != tag.toLowerCase());
        this._sourceInputNode.value = this.tags.join(' ');
        for (let wrapperNode of this._getAllWrapperNodes()) {
            if (this._getTagFromWrapper(wrapperNode).toLowerCase() ==
                    tag.toLowerCase()) {
                if (wrapperNode.contains(document.activeElement)) {
                    const nextWrapperNode = this._getNextWrapper(wrapperNode);
                    const nextInputNode =
                        nextWrapperNode.querySelector('.editable');
                    if (nextInputNode) {
                        nextInputNode.focus();
                    }
                }
                this._editAreaNode.removeChild(wrapperNode);
                break;
            }
        }
    }

    _evtEditAreaClick(e) {
        if (e.target.nodeName.toLowerCase() === 'a') {
            return;
        }

        if (this.readOnly) {
            return;
        }

        e.preventDefault();

        let closestInputNode = null;
        let closestDistance = Infinity;

        const mouseX = e.clientX;
        const mouseY = e.clientY;

        for (let wrapperNode of this._getAllWrapperNodes()) {
            const inputNode = wrapperNode.querySelector('.editable');
            if (!inputNode) {
                continue;
            }
            const inputNodeRect = inputNode.getBoundingClientRect();
            const inputNodeX = inputNodeRect.left;
            const inputNodeY = inputNodeRect.top;
            const distance = Math.sqrt(
                Math.pow(mouseX - inputNodeX, 2) +
                Math.pow(mouseY - inputNodeY, 2));
            if (distance < closestDistance) {
                closestDistance = distance;
                closestInputNode = inputNode;
            }
        }

        if (closestDistance > 25) {
            closestInputNode = this._tailInputNode;
        }

        closestInputNode.focus();
    }

    _evtInputPaste(e) {
        e.preventDefault();
        const pastedText = window.clipboardData ?
            window.clipboardData.getData('Text') :
            (e.originalEvent || e).clipboardData.getData('text/plain');

        if (pastedText.length > 2000) {
            window.alert('Pasted text is too long.');
            return;
        }
        this.addMultipleTags(pastedText);
    }

    _evtInputKeyDown(e) {
        const inputNode = e.target;
        const wrapperNode = this._getWrapperFromChild(inputNode);
        const key = e.which;

        if (key == KEY_A && e.ctrlKey) {
            e.preventDefault();
            e.stopImmediatePropagation();
            e.stopPropagation();
            const selection = window.getSelection();
            const range = document.createRange();
            range.selectNode(this._editAreaNode);
            selection.removeAllRanges();
            selection.addRange(range);
        }

        if (key == KEY_HOME) {
            if (window.getSelection().getRangeAt(0).startOffset !== 0) {
                return;
            }
            e.preventDefault();
            this._getAllWrapperNodes()[0].querySelector('.editable').focus();
        }

        if (key == KEY_END) {
            if (window.getSelection().getRangeAt(0).endOffset !==
                    inputNode.textContent.length) {
                return;
            }
            e.preventDefault();
            this._getAllWrapperNodes()[this._getAllWrapperNodes().length - 1]
                .querySelector('.editable').focus();
        }

        if (key == KEY_LEFT) {
            if (window.getSelection().getRangeAt(0).startOffset !== 0) {
                return;
            }
            e.preventDefault();
            const prevWrapperNode = this._getPreviousWrapper(wrapperNode);
            if (prevWrapperNode) {
                prevWrapperNode.querySelector('.editable').focus();
            }
        }

        if (key == KEY_RIGHT) {
            if (window.getSelection().getRangeAt(0).endOffset !==
                    inputNode.textContent.length) {
                return;
            }
            e.preventDefault();
            const nextWrapperNode = this._getNextWrapper(wrapperNode);
            if (nextWrapperNode) {
                nextWrapperNode.querySelector('.editable').focus();
            }
        }

        if (key == KEY_BACKSPACE) {
            if (inputNode.textContent !== '') {
                return;
            }
            e.preventDefault();
            const prevWrapperNode = this._getPreviousWrapper(wrapperNode);
            this.deleteTag(this._getTagFromWrapper(prevWrapperNode));
        }

        if (key == KEY_DELETE) {
            if (inputNode.textContent !== '') {
                return;
            }
            e.preventDefault();
            if (!wrapperNode.contains(this._mainEditNode)) {
                this.deleteTag(this._getTagFromWrapper(wrapperNode));
            }
        }

        if (key == KEY_RETURN || key == KEY_SPACE) {
            e.preventDefault();
            this.addTag(inputNode.textContent, inputNode, true, true);
            inputNode.innerHTML = '';
        }
    }

    _evtInputBlur(e) {
        const inputNode = e.target;
        this.addTag(inputNode.textContent, inputNode, true, true);
        inputNode.innerHTML = '';
    }

    _evtTagLinkClick(e) {
        e.preventDefault();
        const wrapperNode = this._getWrapperFromChild(e.target);
        const tagName = this._getTagFromWrapper(wrapperNode);
        const actualTag = tags.getTagByName(tagName);
        if (!actualTag) {
            return;
        }
        api.get('/tag-siblings/' + tagName, {noProgress: true})
            .then(response => {
                return Promise.resolve(response.results);
            }, response => {
                return Promise.resolve([]);
            }).then(siblings => {
                const suggestionNames = actualTag.suggestions || [];
                const siblingNames = siblings.map(s => s.tag.names[0]);
                this._suggestRelations(siblingNames, suggestionNames);
            });
    }

    _evtRelationSuggestionLinkClick(e) {
        e.preventDefault();
        this.addTag(e.target.textContent, null, true, true);
    }

    _getWrapperFromChild(startNode) {
        let node = startNode;
        while (node) {
            if ('classList' in node && node.classList.contains('wrapper')) {
                return node;
            }
            node = node.parentNode;
        }
        throw Error('Wrapper node not found');
    }

    _getPreviousWrapper(wrapperNode) {
        let result = wrapperNode.previousSibling;
        while (result && result.nodeType === 3) {
            result = result.previousSibling;
        }
        return result;
    }

    _getNextWrapper(wrapperNode) {
        let result = wrapperNode.nextSibling;
        while (result && result.nodeType === 3) {
            result = result.nextSibling;
        }
        return result;
    }

    _getAllWrapperNodes() {
        const result = [];
        for (let child of this._editAreaNode.childNodes) {
            if (child.nodeType === 3) {
                continue;
            }
            result.push(child);
        }
        return result;
    }

    _getTagFromWrapper(wrapperNode) {
        if (!wrapperNode || !wrapperNode.hasAttribute('data-tag')) {
            return null;
        }
        return wrapperNode.getAttribute('data-tag');
    }

    _getWrapperFromTag(tag) {
        for (let wrapperNode of this._getAllWrapperNodes()) {
            if (this._getTagFromWrapper(wrapperNode).toLowerCase() ==
                    tag.toLowerCase()) {
                return wrapperNode;
            }
        }
        return null;
    }

    _suggestRelations(siblingNames, suggestionNames) {
        this._hideRelationSuggestions();
        siblingNames = siblingNames
            .filter(tag => !this.isTaggedWith(tag));
        suggestionNames = suggestionNames
            .filter(tag => !this.isTaggedWith(tag));

        if (!siblingNames.length && !suggestionNames.length) {
            return;
        }

        const node = this._relationsTemplate({
            siblings: siblingNames,
            suggestions: suggestionNames,
        });

        for (let link of node.querySelectorAll('a')) {
            link.addEventListener(
                'click', e => this._evtRelationSuggestionLinkClick(e));
        }

        // TODO: slide down
        this._editAreaNode.parentNode.insertBefore(
            node, this._editAreaNode.nextSibling);
        views.slideDown(node);
        this._relationsNodes.push(node);
    }

    _createWrapper() {
        return views.htmlToDom('<span class="wrapper"></span>');
    }

    _createSpace(text) {
        // space between elements serves two purposes:
        // - the wrappers play nicely with word-wrap: break-word
        // - copying the input text to clipboard shows spaces
        return document.createTextNode(' ');
    }

    _createInput(text) {
        const inputNode = views.htmlToDom(
            '<span class="editable" contenteditable>');
        const autoCompleteControl = new TagAutoCompleteControl(
            inputNode, {
                getTextToFind: () => {
                    return inputNode.textContent;
                },
                confirm: text => {
                    const wrapperNode = this._getWrapperFromChild(inputNode);
                    inputNode.innerHTML = '';
                    this.addTag(text, inputNode, true, true);
                },
                verticalShift: -2,
            });
        inputNode.addEventListener('keydown', e => this._evtInputKeyDown(e));
        inputNode.addEventListener('paste', e => this._evtInputPaste(e));
        inputNode.addEventListener('blur', e => this._evtInputBlur(e));
        this._autoCompleteControls.push(autoCompleteControl);
        return inputNode;
    }

    _createLink(text) {
        const actualTag = tags.getTagByName(text);
        const link = views.htmlToDom(
            views.makeNonVoidElement(
                'a',
                {
                    class: actualTag ?
                        misc.makeCssName(actualTag.category, 'tag') :
                        '',
                    href: '/tag/' + text,
                },
                text));
        link.addEventListener('click', e=> this._evtTagLinkClick(e));
        return link;
    }

    _hideRelationSuggestions() {
        while (this._relationsNodes.length) {
            const node = this._relationsNodes.pop(0);
            views.slideUp(node).then(() => node.parentNode.removeChild(node));
        }
    }

    _hideAutoComplete() {
        for (let autoCompleteControl of this._autoCompleteControls) {
            autoCompleteControl.hide();
        }
    }

    _hideVisualCues() {
        for (let wrapperNode of this._getAllWrapperNodes()) {
            wrapperNode.classList.remove('duplicate');
        }
        this._hideAutoComplete();
        this._hideRelationSuggestions();
    }
}

module.exports = TagInputControl;
