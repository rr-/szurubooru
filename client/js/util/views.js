'use strict';

require('../util/polyfill.js');
const api = require('../api.js');
const templates = require('../templates.js');
const tags = require('../tags.js');
const domParser = new DOMParser();
const misc = require('./misc.js');

function _imbueId(options) {
    if (!options.id) {
        options.id = 'gen-' + Math.random().toString(36).substring(7);
    }
}

function _makeLabel(options, attrs) {
    if (!options.text) {
        return '';
    }
    if (!attrs) {
        attrs = {};
    }
    attrs.for = options.id;
    return makeNonVoidElement('label', attrs, options.text);
}

function makeFileSize(fileSize) {
    return misc.formatFileSize(fileSize);
}

function makeMarkdown(text) {
    return misc.formatMarkdown(text);
}

function makeRelativeTime(time) {
    return makeNonVoidElement(
        'time',
        {datetime: time, title: time},
        misc.formatRelativeTime(time));
}

function makeThumbnail(url) {
    return makeNonVoidElement(
        'span',
        {
            class: 'thumbnail',
            style: `background-image: url(\'${url}\')`,
        },
        makeVoidElement('img', {alt: 'thumbnail', src: url}));
}

function makeRadio(options) {
    _imbueId(options);
    return makeVoidElement(
        'input',
        {
            id: options.id,
            name: options.name,
            value: options.value,
            type: 'radio',
            checked: options.selectedValue === options.value,
            disabled: options.readonly,
            required: options.required,
        }) +
    _makeLabel(options, {class: 'radio'});
}

function makeCheckbox(options) {
    _imbueId(options);
    return makeVoidElement(
        'input',
        {
            id: options.id,
            name: options.name,
            value: options.value,
            type: 'checkbox',
            checked: options.checked !== undefined ?
                options.checked : false,
            disabled: options.readonly,
            required: options.required,
        }) +
    _makeLabel(options, {class: 'checkbox'});
}

function makeSelect(options) {
    return _makeLabel(options) +
        makeNonVoidElement(
            'select',
            {
                id: options.id,
                name: options.name,
                disabled: options.readonly,
            },
            Object.keys(options.keyValues).map(key => {
                return makeNonVoidElement(
                    'option',
                    {value: key, selected: key === options.selectedKey},
                    options.keyValues[key]);
            }).join(''));
}

function makeInput(options) {
    options.value = options.value || '';
    return _makeLabel(options) + makeVoidElement('input', options);
}

function makeButton(options) {
    options.type = 'button';
    return makeInput(options);
}

function makeTextInput(options) {
    options.type = 'text';
    return makeInput(options);
}

function makeTextarea(options) {
    const value = options.value || '';
    delete options.value;
    return _makeLabel(options) + makeNonVoidElement('textarea', options, value);
}

function makePasswordInput(options) {
    options.type = 'password';
    return makeInput(options);
}

function makeEmailInput(options) {
    options.type = 'email';
    return makeInput(options);
}

function makeColorInput(options) {
    const textInput = makeVoidElement(
        'input', {
            type: 'text',
            value: options.value || '',
            required: options.required,
            style: 'color: ' + options.value,
            disabled: true,
        });
    const colorInput = makeVoidElement(
        'input', {
            type: 'color',
            value: options.value || '',
        });
    return makeNonVoidElement(
        'label', {class: 'color'}, colorInput + textInput);
}

function getPostUrl(id, parameters) {
    let url = '/post/' + encodeURIComponent(id);
    if (parameters && parameters.query) {
        url += '/query=' + encodeURIComponent(parameters.query);
    }
    return url;
}

function getPostEditUrl(id, parameters) {
    let url = '/post/' + encodeURIComponent(id) + '/edit';
    if (parameters && parameters.query) {
        url += '/query=' + encodeURIComponent(parameters.query);
    }
    return url;
}

function makePostLink(id) {
    const text = '@' + id;
    return api.hasPrivilege('posts:view') ?
        makeNonVoidElement(
            'a', {'href': '/post/' + encodeURIComponent(id)}, text) :
        text;
}

function makeTagLink(name) {
    const tag = tags.getTagByName(name);
    const category = tag ? tag.category : 'unknown';
    return api.hasPrivilege('tags:view') ?
        makeNonVoidElement(
            'a', {
                'href': '/tag/' + encodeURIComponent(name),
                'class': misc.makeCssName(category, 'tag'),
            }, name) :
        makeNonVoidElement(
            'span', {
                'class': misc.makeCssName(category, 'tag'),
            },
            name);
}

function makeUserLink(user) {
    const text = makeThumbnail(user.avatarUrl) + user.name;
    const link = api.hasPrivilege('users:view') ?
        makeNonVoidElement(
            'a', {'href': '/user/' + encodeURIComponent(user.name)}, text) :
        text;
    return makeNonVoidElement('span', {class: 'user'}, link);
}

function makeFlexboxAlign(options) {
    return Array.from(misc.range(20))
        .map(() => '<li class="flexbox-dummy"></li>').join('');
}

function makeAccessKey(html, key) {
    const regex = new RegExp('(' + key + ')', 'i');
    html = html.replace(
        regex, '<span class="access-key" data-accesskey="$1">$1</span>');
    return html;
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
            const attribute = misc.escapeHtml(attributes[key] || '');
            return `${key}="${attribute}"`;
        }))
        .join(' ');
}

function makeNonVoidElement(name, attributes, content) {
    return `<${_serializeElement(name, attributes)}>${content}</${name}>`;
}

function makeVoidElement(name, attributes) {
    return `<${_serializeElement(name, attributes)}/>`;
}

function showMessage(target, message, className) {
    if (!message) {
        message = 'Unknown message';
    }
    const messagesHolder = target.querySelector('.messages');
    if (!messagesHolder) {
        return false;
    }
    /* TODO: animate this */
    const node = document.createElement('div');
    node.innerHTML = message.replace(/\n/g, '<br/>');
    node.classList.add('message');
    node.classList.add(className);
    const wrapper = document.createElement('div');
    wrapper.classList.add('message-wrapper');
    wrapper.appendChild(node);
    messagesHolder.appendChild(wrapper);
    return true;
}

function showError(target, message) {
    return showMessage(target, message, 'error');
}

function showSuccess(target, message) {
    return showMessage(target, message, 'success');
}

function showInfo(target, message) {
    return showMessage(target, message, 'info');
}

function clearMessages(target) {
    const messagesHolder = target.querySelector('.messages');
    /* TODO: animate that */
    while (messagesHolder.lastChild) {
        messagesHolder.removeChild(messagesHolder.lastChild);
    }
}

function htmlToDom(html) {
    // code taken from jQuery + Krasimir Tsonev's blog
    const wrapMap = {
        _:      [1, '<div>', '</div>'],
        option: [1, '<select multiple>', '</select>'],
        legend: [1, '<fieldset>', '</fieldset>'],
        area:   [1, '<map>', '</map>'],
        param:  [1, '<object>', '</object>'],
        thead:  [1, '<table>', '</table>'],
        tr:     [2, '<table><tbody>', '</tbody></table>'],
        td:     [3, '<table><tbody><tr>', '</tr></tbody></table>'],
        col:    [2, '<table><tbody></tbody><colgroup>', '</colgroup></table>'],
    };
    wrapMap.optgroup = wrapMap.option;
    wrapMap.tbody =
        wrapMap.tfoot =
        wrapMap.colgroup =
        wrapMap.caption =
        wrapMap.thead;
    wrapMap.th = wrapMap.td;

    let element = document.createElement('div');
    const match = /<\s*(\w+)[^>]*?>/g.exec(html);

    if (match) {
        const tag = match[1];
        const [depthToChild, prefix, suffix] = wrapMap[tag] || wrapMap._;
        element.innerHTML = prefix + html + suffix;
        for (let i = 0; i < depthToChild; i++) {
            element = element.lastChild;
        }
    } else {
        element.innerHTML = html;
    }
    return element.childNodes.length > 1 ?
        element.childNodes :
        element.firstChild;
}

function getTemplate(templatePath) {
    if (!(templatePath in templates)) {
        throw `Missing template: ${templatePath}`;
    }
    const templateFactory = templates[templatePath];
    return ctx => {
        if (!ctx) {
            ctx = {};
        }
        Object.assign(ctx, {
            getPostUrl: getPostUrl,
            getPostEditUrl: getPostEditUrl,
            makeRelativeTime: makeRelativeTime,
            makeFileSize: makeFileSize,
            makeMarkdown: makeMarkdown,
            makeThumbnail: makeThumbnail,
            makeRadio: makeRadio,
            makeCheckbox: makeCheckbox,
            makeSelect: makeSelect,
            makeInput: makeInput,
            makeButton: makeButton,
            makeTextarea: makeTextarea,
            makeTextInput: makeTextInput,
            makePasswordInput: makePasswordInput,
            makeEmailInput: makeEmailInput,
            makeColorInput: makeColorInput,
            makePostLink: makePostLink,
            makeTagLink: makeTagLink,
            makeUserLink: makeUserLink,
            makeFlexboxAlign: makeFlexboxAlign,
            makeAccessKey: makeAccessKey,
            makeCssName: misc.makeCssName,
        });
        return htmlToDom(templateFactory(ctx));
    };
}

function decorateValidator(form) {
    // postpone showing form fields validity until user actually tries
    // to submit it (seeing red/green form w/o doing anything breaks POLA)
    let submitButton = form.querySelector('.buttons input');
    if (!submitButton) {
        submitButton = form.querySelector('input[type=submit]');
    }
    if (submitButton) {
        submitButton.addEventListener('click', e => {
            form.classList.add('show-validation');
        });
    }
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

function replaceContent(target, source) {
    while (target.lastChild) {
        target.removeChild(target.lastChild);
    }
    if (source instanceof NodeList) {
        for (let child of Array.from(source)) {
            target.appendChild(child);
        }
    } else if (source instanceof Node) {
        target.appendChild(source);
    } else if (source !== null) {
        throw `Invalid view source: ${source}`;
    }
}

function syncScrollPosition() {
    window.requestAnimationFrame(
        () => {
            if (history.state.hasOwnProperty('scrollX')) {
                window.scrollTo(history.state.scrollX, history.state.scrollY);
            } else {
                window.scrollTo(0, 0);
            }
        });
}

function slideDown(element) {
    const duration = 500;
    return new Promise((resolve, reject) => {
        const height = element.getBoundingClientRect().height;
        element.style.maxHeight = '0';
        element.style.overflow = 'hidden';
        window.setTimeout(() => {
            element.style.transition = `all ${duration}ms ease`;
            element.style.maxHeight = `${height}px`;
        }, 50);
        window.setTimeout(() => {
            resolve();
        }, duration);
    });
}

function slideUp(element) {
    const duration = 500;
    return new Promise((resolve, reject) => {
        const height = element.getBoundingClientRect().height;
        element.style.overflow = 'hidden';
        element.style.maxHeight = `${height}px`;
        element.style.transition = `all ${duration}ms ease`;
        window.setTimeout(() => {
            element.style.maxHeight = 0;
        }, 10);
        window.setTimeout(() => {
            resolve();
        }, duration);
    });
}

function monitorNodeRemoval(monitoredNode, callback) {
    const mutationObserver = new MutationObserver(
        mutations => {
            for (let mutation of mutations) {
                for (let node of mutation.removedNodes) {
                    if (node.contains(monitoredNode)) {
                        mutationObserver.disconnect();
                        callback();
                        return;
                    }
                }
            }
        });
    mutationObserver.observe(
        document.body, {childList: true, subtree: true});
}

document.addEventListener('input', e => {
    const type = e.target.getAttribute('type');
    if (type && type.toLowerCase() === 'color') {
        const textInput = e.target.parentNode.querySelector('input[type=text]');
        textInput.style.color = e.target.value;
        textInput.value = e.target.value;
    }
});

module.exports = {
    htmlToDom: htmlToDom,
    getTemplate: getTemplate,
    replaceContent: replaceContent,
    enableForm: enableForm,
    disableForm: disableForm,
    decorateValidator: decorateValidator,
    makeVoidElement: makeVoidElement,
    makeNonVoidElement: makeNonVoidElement,
    syncScrollPosition: syncScrollPosition,
    slideDown: slideDown,
    slideUp: slideUp,
    monitorNodeRemoval: monitorNodeRemoval,
    clearMessages: clearMessages,
    showError: showError,
    showSuccess: showSuccess,
    showInfo: showInfo,
};
