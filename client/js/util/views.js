"use strict";

require("../util/polyfill.js");
const api = require("../api.js");
const templates = require("../templates.js");
const domParser = new DOMParser();
const misc = require("./misc.js");
const uri = require("./uri.js");

function _imbueId(options) {
    if (!options.id) {
        options.id = "gen-" + Math.random().toString(36).substring(7);
    }
}

function _makeLabel(options, attrs) {
    if (!options.text) {
        return "";
    }
    if (!attrs) {
        attrs = {};
    }
    attrs.for = options.id;
    return makeElement("label", attrs, options.text);
}

function makeFileSize(fileSize) {
    return misc.formatFileSize(fileSize);
}

function makeMarkdown(text) {
    return misc.formatMarkdown(text);
}

function makeRelativeTime(time) {
    return makeElement(
        "time",
        { datetime: time, title: time },
        misc.formatRelativeTime(time)
    );
}

function makeThumbnail(url, klass) {
    return makeElement(
        "span",
        url
            ? {
                  class: klass || "thumbnail",
                  style: `background-image: url(\'${url}\')`,
              }
            : { class: "thumbnail empty" },
        makeElement("img", { alt: "thumbnail", src: url })
    );
}

function makePoolThumbnails(posts, postFlow) {
    if (posts.length == 0) {
        return makeThumbnail(null);
    }
    if (postFlow) {
        return makeThumbnail(posts.at(0).thumbnailUrl);
    }

    let s = "";

    for (let i = 0; i < Math.min(3, posts.length); i++) {
        s += makeThumbnail(posts.at(i).thumbnailUrl, "thumbnail thumbnail-" + (i+1));
    }

    return s;
}

function makeRadio(options) {
    _imbueId(options);
    return makeElement(
        "label",
        { for: options.id },
        makeElement("input", {
            id: options.id,
            name: options.name,
            value: options.value,
            type: "radio",
            checked: options.selectedValue === options.value,
            disabled: options.readonly,
            required: options.required,
        }),
        makeElement("span", { class: "radio" }, options.text)
    );
}

function makeCheckbox(options) {
    _imbueId(options);
    return makeElement(
        "label",
        { for: options.id },
        makeElement("input", {
            id: options.id,
            name: options.name,
            value: options.value,
            type: "checkbox",
            checked: options.checked !== undefined ? options.checked : false,
            disabled: options.readonly,
            required: options.required,
        }),
        makeElement("span", { class: "checkbox" }, options.text)
    );
}

function makeSelect(options) {
    return (
        _makeLabel(options) +
        makeElement(
            "select",
            {
                id: options.id,
                name: options.name,
                disabled: options.readonly,
            },
            ...Object.keys(options.keyValues).map((key) =>
                makeElement(
                    "option",
                    { value: key, selected: key === options.selectedKey },
                    options.keyValues[key]
                )
            )
        )
    );
}

function makeInput(options) {
    options.value = options.value || "";
    return _makeLabel(options) + makeElement("input", options);
}

function makeButton(options) {
    options.type = "button";
    return makeInput(options);
}

function makeTextInput(options) {
    options.type = "text";
    return makeInput(options);
}

function makeTextarea(options) {
    const value = options.value || "";
    delete options.value;
    return _makeLabel(options) + makeElement("textarea", options, value);
}

function makePasswordInput(options) {
    options.type = "password";
    return makeInput(options);
}

function makeEmailInput(options) {
    options.type = "email";
    return makeInput(options);
}

function makeColorInput(options) {
    const textInput = makeElement("input", {
        type: "text",
        value: options.value || "",
        required: options.required,
        class: "color",
    });
    const backgroundPreviewNode = makeElement("div", {
        class: "preview background-preview",
        style: `border-color: ${options.value};
                background-color: ${options.value}`,
    });
    const textPreviewNode = makeElement("div", {
        class: "preview text-preview",
        style: `border-color: ${options.value};
                color: ${options.value}`,
    });
    return makeElement(
        "label",
        { class: "color" },
        textInput,
        backgroundPreviewNode,
        textPreviewNode
    );
}

function makeNumericInput(options) {
    options.type = "number";
    return makeInput(options);
}

function makeDateInput(options) {
    options.type = "date";
    return makeInput(options);
}

function getPostUrl(id, parameters) {
    return uri.formatClientLink(
        "post",
        id,
        parameters ? { query: parameters.query } : {}
    );
}

function getPostEditUrl(id, parameters) {
    return uri.formatClientLink(
        "post",
        id,
        "edit",
        parameters ? { query: parameters.query } : {}
    );
}

function makePostLink(id, includeHash) {
    let text = id;
    if (includeHash) {
        text = "@" + id;
    }
    return api.hasPrivilege("posts:view")
        ? makeElement(
              "a",
              { href: uri.formatClientLink("post", id) },
              misc.escapeHtml(text)
          )
        : misc.escapeHtml(text);
}

function makeTagLink(name, includeHash, includeCount, tag) {
    const category = tag ? tag.category : "unknown";
    let text = misc.getPrettyName(name);
    if (includeHash === true) {
        text = "#" + text;
    }
    if (includeCount === true) {
        text += " (" + (tag ? tag.postCount : 0) + ")";
    }
    return api.hasPrivilege("tags:view")
        ? makeElement(
              "a",
              {
                  href: uri.formatClientLink("tag", name),
                  class: misc.makeCssName(category, "tag"),
              },
              misc.escapeHtml(text)
          )
        : makeElement(
              "span",
              { class: misc.makeCssName(category, "tag") },
              misc.escapeHtml(text)
          );
}

function makePoolLink(id, includeHash, includeCount, pool, name) {
    const category = pool ? pool.category : "unknown";
    let text = misc.getPrettyName(
        name ? name : pool ? pool.names[0] : "unknown"
    );
    if (includeHash === true) {
        text = "#" + text;
    }
    if (includeCount === true) {
        text += " (" + (pool ? pool.postCount : 0) + ")";
    }
    return api.hasPrivilege("pools:view")
        ? makeElement(
              "a",
              {
                  href: uri.formatClientLink("pool", id),
                  class: misc.makeCssName(category, "pool"),
              },
              misc.escapeHtml(text)
          )
        : makeElement(
              "div",
              { class: misc.makeCssName(category, "pool") },
              misc.escapeHtml(text)
          );
}

function makeUserLink(user) {
    let text = makeThumbnail(user ? user.avatarUrl : null);
    text += user && user.name ? misc.escapeHtml(user.name) : "Anonymous";
    const link =
        user && api.hasPrivilege("users:view")
            ? makeElement(
                  "a",
                  { href: uri.formatClientLink("user", user.name) },
                  text
              )
            : text;
    return makeElement("span", { class: "user" }, link);
}

function makeFlexboxAlign(options) {
    return [...misc.range(20)]
        .map(() => '<li class="flexbox-dummy"></li>')
        .join("");
}

function makeAccessKey(html, key) {
    const regex = new RegExp("(" + key + ")", "i");
    html = html.replace(
        regex,
        '<span class="access-key" data-accesskey="$1">$1</span>'
    );
    return html;
}

function _serializeElement(name, attributes) {
    return [name]
        .concat(
            Object.keys(attributes).map((key) => {
                if (attributes[key] === true) {
                    return key;
                } else if (
                    attributes[key] === false ||
                    attributes[key] === undefined
                ) {
                    return "";
                }
                const attribute = misc.escapeHtml(attributes[key] || "");
                return `${key}="${attribute}"`;
            })
        )
        .join(" ");
}

function makeElement(name, attrs, ...content) {
    return content.length !== undefined
        ? `<${_serializeElement(name, attrs)}>${content.join("")}</${name}>`
        : `<${_serializeElement(name, attrs)}/>`;
}

function emptyContent(target) {
    while (target.lastChild) {
        target.removeChild(target.lastChild);
    }
}

function replaceContent(target, source) {
    emptyContent(target);
    if (source instanceof NodeList) {
        for (let child of [...source]) {
            target.appendChild(child);
        }
    } else if (source instanceof Node) {
        target.appendChild(source);
    } else if (source !== null) {
        throw `Invalid view source: ${source}`;
    }
}

function showMessage(target, message, className) {
    if (!message) {
        message = "Unknown message";
    }
    const messagesHolderNode = target.querySelector(".messages");
    if (!messagesHolderNode) {
        return false;
    }
    const textNode = document.createElement("div");
    textNode.innerHTML = message.replace(/\n/g, "<br/>");
    textNode.classList.add("message");
    textNode.classList.add(className);
    const wrapperNode = document.createElement("div");
    wrapperNode.classList.add("message-wrapper");
    wrapperNode.appendChild(textNode);
    messagesHolderNode.appendChild(wrapperNode);
    return true;
}

function appendExclamationMark() {
    if (!document.title.startsWith("!")) {
        document.oldTitle = document.title;
        document.title = `! ${document.title}`;
    }
}

function showError(target, message) {
    appendExclamationMark();
    return showMessage(target, misc.formatInlineMarkdown(message), "error");
}

function showSuccess(target, message) {
    return showMessage(target, misc.formatInlineMarkdown(message), "success");
}

function showInfo(target, message) {
    return showMessage(target, misc.formatInlineMarkdown(message), "info");
}

function clearMessages(target) {
    if (document.oldTitle) {
        document.title = document.oldTitle;
        document.oldTitle = null;
    }
    for (let messagesHolderNode of target.querySelectorAll(".messages")) {
        emptyContent(messagesHolderNode);
    }
}

function htmlToDom(html) {
    // code taken from jQuery + Krasimir Tsonev's blog
    const wrapMap = {
        _: [1, "<div>", "</div>"],
        option: [1, "<select multiple>", "</select>"],
        legend: [1, "<fieldset>", "</fieldset>"],
        area: [1, "<map>", "</map>"],
        param: [1, "<object>", "</object>"],
        thead: [1, "<table>", "</table>"],
        tr: [2, "<table><tbody>", "</tbody></table>"],
        td: [3, "<table><tbody><tr>", "</tr></tbody></table>"],
        col: [2, "<table><tbody></tbody><colgroup>", "</colgroup></table>"],
    };
    wrapMap.optgroup = wrapMap.option;
    wrapMap.tbody = wrapMap.thead;
    wrapMap.tfoot = wrapMap.thead;
    wrapMap.colgroup = wrapMap.thead;
    wrapMap.caption = wrapMap.thead;
    wrapMap.th = wrapMap.td;

    let element = document.createElement("div");
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
    return element.childNodes.length > 1
        ? element.childNodes
        : element.firstChild;
}

function getTemplate(templatePath) {
    if (!(templatePath in templates)) {
        throw `Missing template: ${templatePath}`;
    }
    const templateFactory = templates[templatePath];
    return (ctx) => {
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
            makePoolThumbnails: makePoolThumbnails,
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
            makeDateInput: makeDateInput,
            makePostLink: makePostLink,
            makeTagLink: makeTagLink,
            makePoolLink: makePoolLink,
            makeUserLink: makeUserLink,
            makeFlexboxAlign: makeFlexboxAlign,
            makeAccessKey: makeAccessKey,
            makeElement: makeElement,
            makeCssName: misc.makeCssName,
            makeNumericInput: makeNumericInput,
            formatClientLink: uri.formatClientLink,
        });
        return htmlToDom(templateFactory(ctx));
    };
}

function decorateValidator(form) {
    // postpone showing form fields validity until user actually tries
    // to submit it (seeing red/green form w/o doing anything breaks POLA)
    let submitButton = form.querySelector(".buttons input");
    if (!submitButton) {
        submitButton = form.querySelector("input[type=submit]");
    }
    if (submitButton) {
        submitButton.addEventListener("click", (e) => {
            form.classList.add("show-validation");
        });
    }
    form.addEventListener("submit", (e) => {
        form.classList.remove("show-validation");
    });
}

function disableForm(form) {
    for (let input of form.querySelectorAll("input")) {
        input.disabled = true;
    }
}

function enableForm(form) {
    for (let input of form.querySelectorAll("input")) {
        input.disabled = false;
    }
}

function syncScrollPosition() {
    window.requestAnimationFrame(() => {
        if (
            history.state &&
            Object.prototype.hasOwnProperty.call(history.state, "scrollX")
        ) {
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
        element.style.maxHeight = "0";
        element.style.overflow = "hidden";
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
        element.style.overflow = "hidden";
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
    const mutationObserver = new MutationObserver((mutations) => {
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
    mutationObserver.observe(document.body, {
        childList: true,
        subtree: true,
    });
}

document.addEventListener("input", (e) => {
    if (e.target.classList.contains("color")) {
        let bkNode = e.target.parentNode.querySelector(".background-preview");
        let textNode = e.target.parentNode.querySelector(".text-preview");
        bkNode.style.backgroundColor = e.target.value;
        bkNode.style.borderColor = e.target.value;
        textNode.style.color = e.target.value;
        textNode.style.borderColor = e.target.value;
    }
});

// prevent opening buttons in new tabs
document.addEventListener("click", (e) => {
    if (e.target.getAttribute("href") === "" && e.which === 2) {
        e.preventDefault();
    }
});

module.exports = {
    htmlToDom: htmlToDom,
    getTemplate: getTemplate,
    emptyContent: emptyContent,
    replaceContent: replaceContent,
    enableForm: enableForm,
    disableForm: disableForm,
    decorateValidator: decorateValidator,
    makeTagLink: makeTagLink,
    makePostLink: makePostLink,
    makePoolLink: makePoolLink,
    makeCheckbox: makeCheckbox,
    makeRadio: makeRadio,
    syncScrollPosition: syncScrollPosition,
    slideDown: slideDown,
    slideUp: slideUp,
    monitorNodeRemoval: monitorNodeRemoval,
    clearMessages: clearMessages,
    appendExclamationMark: appendExclamationMark,
    showError: showError,
    showSuccess: showSuccess,
    showInfo: showInfo,
};
