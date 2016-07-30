'use strict';

const api = require('../api.js');
const tags = require('../tags.js');
const misc = require('../util/misc.js');
const events = require('../events.js');
const views = require('../util/views.js');
const TagAutoCompleteControl = require('./tag_auto_complete_control.js');

const KEY_SPACE = 32;
const KEY_RETURN = 13;

class TagInputControl extends events.EventTarget {
    constructor(sourceInputNode) {
        super();
        this.tags = [];

        this._relationsTemplate = views.getTemplate('tag-relations');
        this._sourceInputNode = sourceInputNode;

        this._install();
    }

    _install() {
        this._editAreaNode = document.createElement('div');
        this._editAreaNode.classList.add('tag-input');

        this._tagInputNode = views.htmlToDom(
            '<input type="text" placeholder="type to add…"/>');
        this._autoCompleteControl = new TagAutoCompleteControl(
            this._tagInputNode, {
                getTextToFind: () => {
                    return this._tagInputNode.value;
                },
                confirm: text => {
                    this._tagInputNode.value = '';
                    this.addTag(text, true);
                },
                verticalShift: -2,
            });
        this._tagInputNode.addEventListener(
            'keydown', e => this._evtInputKeyDown(e));
        this._tagInputNode.addEventListener(
            'paste', e => this._evtInputPaste(e));
        this._editAreaNode.appendChild(this._tagInputNode);

        this._tagListNode = views.htmlToDom('<ul class="compact-tags"></ul>');
        this._editAreaNode.appendChild(this._tagListNode);

        // show
        this._sourceInputNode.style.display = 'none';
        this._sourceInputNode.parentNode.insertBefore(
            this._editAreaNode, this._sourceInputNode.nextSibling);

        this.addEventListener('change', e => this._evtTagsChanged(e));
        this.addEventListener('add', e => this._evtTagAdded(e));
        this.addEventListener('remove', e => this._evtTagRemoved(e));

        // add existing tags
        this.addMultipleTags(this._sourceInputNode.value, false);
    }

    isTaggedWith(tagName) {
        return this.tags
            .map(t => t.toLowerCase())
            .includes(tagName.toLowerCase());
    }

    addMultipleTags(text, addImplications) {
        for (let tagName of text.split(/\s+/).filter(word => word).reverse()) {
            this.addTag(tagName, addImplications);
        }
    }

    addTag(tagName, addImplications) {
        tagName = tags.getOriginalTagName(tagName);

        if (!tagName) {
            return;
        }

        if (!this.isTaggedWith(tagName)) {
            this.tags.push(tagName);
        }
        this.dispatchEvent(new CustomEvent('add', {
            detail: {
                tagName: tagName,
            },
        }));
        this.dispatchEvent(new CustomEvent('change'));

        // XXX: perhaps we should aggregate suggestions from all implications
        // for call to the _suggestRelations
        if (addImplications) {
            for (let otherTagName of tags.getAllImplications(tagName)) {
                this.addTag(otherTagName, true, false);
            }
        }
    }

    deleteTag(tagName) {
        if (!tagName) {
            return;
        }
        if (!this.isTaggedWith(tagName)) {
            return;
        }
        this._hideAutoComplete();
        this.tags = this.tags.filter(
            t => t.toLowerCase() != tagName.toLowerCase());
        this.dispatchEvent(new CustomEvent('remove', {
            detail: {
                tagName: tagName,
            },
        }));
        this.dispatchEvent(new CustomEvent('change'));
    }

    _evtTagsChanged(e) {
        this._sourceInputNode.value = this.tags.join(' ');
    }

    _evtTagAdded(e) {
        const tagName = e.detail.tagName;
        let listItemNode = this._getListItemNodeFromTagName(tagName);
        if (listItemNode) {
            listItemNode.classList.add('duplicate');
        } else {
            listItemNode = this._createListItemNode(tagName);
            if (!tags.getTagByName(tagName)) {
                listItemNode.classList.add('new');
            }
            this._tagListNode.prependChild(listItemNode);
        }
    }

    _evtTagRemoved(e) {
        const listItemNode = this._getListItemNodeFromTagName(e.detail.tagName);
        if (listItemNode) {
            listItemNode.parentNode.removeChild(listItemNode);
        }
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
        this._addTagsFromInput(pastedText);
    }

    _evtInputKeyDown(e) {
        if (e.which == KEY_RETURN || e.which == KEY_SPACE) {
            e.preventDefault();
            this._addTagsFromInput(this._tagInputNode.value);
        }
    }

    _getListItemNodeFromTagName(tagName) {
        for (let listItemNode of this._tagListNode.querySelectorAll('li')) {
            if (listItemNode.getAttribute('data-tag').toLowerCase() ===
                    tagName.toLowerCase()) {
                return listItemNode;
            }
        }
        return null;
    }

    _addTagsFromInput(text) {
        this._hideAutoComplete();
        this.addMultipleTags(text, true);
        this._tagInputNode.value = '';
        // TODO: suggest relations!
    }

    _createListItemNode(tagName) {
        const actualTag = tags.getTagByName(tagName);
        const className = actualTag ?
            misc.makeCssName(actualTag.category, 'tag') :
            '';

        const tagLinkNode = views.htmlToDom(
            views.makeNonVoidElement(
                'a',
                {
                    class: className,
                    href: '/tag/' + encodeURIComponent(tagName),
                },
                '<i class="fa fa-tag"></i>'));

        const searchLinkNode = views.htmlToDom(
            views.makeNonVoidElement(
                'a',
                {
                    class: className,
                    href: '/posts/query=' + encodeURIComponent(tagName),
                },
                actualTag ? actualTag.names[0] : tagName));

        const usagesNode = views.htmlToDom(
            views.makeNonVoidElement(
                'span',
                {class: 'count'},
                actualTag ? actualTag.usages : 0));

        const removalLinkNode = views.htmlToDom(
            views.makeNonVoidElement(
                'a',
                {href: '#', class: 'count'},
                '×'));
        removalLinkNode.addEventListener('click', e => {
            e.preventDefault();
            this.deleteTag(tagName);
        });

        const listItemNode = document.createElement('li');
        listItemNode.setAttribute('data-tag', tagName);
        listItemNode.appendChild(tagLinkNode);
        listItemNode.appendChild(searchLinkNode);
        listItemNode.appendChild(usagesNode);
        listItemNode.appendChild(removalLinkNode);
        return listItemNode;
    }

    _hideAutoComplete() {
        this._autoCompleteControl.hide();
    }
}

module.exports = TagInputControl;
