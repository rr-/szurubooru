"use strict";

const api = require("../api.js");
const settings = require("../models/settings.js");
const uri = require("./uri.js");

const MAX_HISTORY_ITEMS = 50;
const HISTORY_KEY = "szurubooru-search-history-anonymous";

function getQueryHistory() {
    if (settings.get().searchHistoryEnabled === false) {
        return [];
    }
    if (api.userName && api.user) {
        try {
            return api.user.searchHistory ? JSON.parse(api.user.searchHistory) : [];
        } catch (e) {
            return [];
        }
    }
    try {
        const item = localStorage.getItem(HISTORY_KEY);
        return item ? JSON.parse(item) : [];
    } catch (e) {
        return [];
    }
}

function addQueryToHistory(query) {
    if (settings.get().searchHistoryEnabled === false) {
        return;
    }
    if (!query || !query.trim()) return;
    query = query.trim();
    let history = getQueryHistory();
    history = history.filter((q) => q !== query);
    history.unshift(query);
    if (history.length > MAX_HISTORY_ITEMS) {
        history = history.slice(0, MAX_HISTORY_ITEMS);
    }
    saveHistory(history);
}

function removeQueryFromHistory(query) {
    let history = getQueryHistory();
    history = history.filter((q) => q !== query);
    saveHistory(history);
}

function clearQueryHistory() {
    saveHistory([]);
}

function saveHistory(history) {
    if (api.userName && api.user) {
        api.user.searchHistory = JSON.stringify(history);
        api.put(uri.formatApiLink("user", api.userName), {
            version: api.user.version,
            searchHistory: api.user.searchHistory
        }).then(
            (response) => {
                api.user = response;
            },
            (err) => {
                console.error("Failed to save search history to account:", err);
            }
        );
    } else {
        try {
            localStorage.setItem(HISTORY_KEY, JSON.stringify(history));
        } catch (e) {
            // Ignore quota/access errors
        }
    }
}

module.exports = {
    getQueryHistory: getQueryHistory,
    addQueryToHistory: addQueryToHistory,
    removeQueryFromHistory: removeQueryFromHistory,
    clearQueryHistory: clearQueryHistory,
};
