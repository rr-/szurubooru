"use strict";

const api = require("../api.js");
const views = require("../util/views.js");

const template = views.getTemplate("help");
const sectionTemplates = {
    about: views.getTemplate("help-about"),
    keyboard: views.getTemplate("help-keyboard"),
    search: views.getTemplate("help-search"),
    comments: views.getTemplate("help-comments"),
    tos: views.getTemplate("help-tos"),
};
const subsectionTemplates = {
    search: {
        default: views.getTemplate("help-search-general"),
        posts: views.getTemplate("help-search-posts"),
        users: views.getTemplate("help-search-users"),
        tags: views.getTemplate("help-search-tags"),
        pools: views.getTemplate("help-search-pools"),
    },
};

class HelpView {
    constructor(section, subsection) {
        this._hostNode = document.getElementById("content-holder");

        const sourceNode = template();
        const ctx = {
            name: api.getName(),
        };

        section = section || "about";
        if (section in sectionTemplates) {
            views.replaceContent(
                sourceNode.querySelector(".content"),
                sectionTemplates[section](ctx)
            );
        }

        subsection = subsection || "default";
        if (
            section in subsectionTemplates &&
            subsection in subsectionTemplates[section]
        ) {
            views.replaceContent(
                sourceNode.querySelector(".subcontent"),
                subsectionTemplates[section][subsection](ctx)
            );
        }

        views.replaceContent(this._hostNode, sourceNode);

        for (let itemNode of sourceNode.querySelectorAll(
            ".primary [data-name]"
        )) {
            itemNode.classList.toggle(
                "active",
                itemNode.getAttribute("data-name") === section
            );
            if (itemNode.getAttribute("data-name") === section) {
                itemNode.parentNode.scrollLeft =
                    itemNode.getBoundingClientRect().left -
                    itemNode.parentNode.getBoundingClientRect().left;
            }
        }

        for (let itemNode of sourceNode.querySelectorAll(
            ".secondary [data-name]"
        )) {
            itemNode.classList.toggle(
                "active",
                itemNode.getAttribute("data-name") === subsection
            );
            if (itemNode.getAttribute("data-name") === subsection) {
                itemNode.parentNode.scrollLeft =
                    itemNode.getBoundingClientRect().left -
                    itemNode.parentNode.getBoundingClientRect().left;
            }
        }

        views.syncScrollPosition();
    }
}

module.exports = HelpView;
