'use strict';

function formatApiLink(...values) {
    let parts = [];
    for (let value of values) {
        if (value.constructor === Object) {
            // assert this is the last piece
            let variableParts = [];
            for (let key of Object.keys(value)) {
                if (value[key]) {
                    variableParts.push(
                        key + '=' + encodeURIComponent(value[key].toString()));
                }
            }
            if (variableParts.length) {
                parts.push('?' + variableParts.join('&'));
            }
            break;
        } else {
            parts.push(encodeURIComponent(value.toString()));
        }
    }
    return '/' + parts.join('/');
}

function escapeParam(text) {
    return encodeURIComponent(text);
}

function unescapeParam(text) {
    return decodeURIComponent(text);
}

function formatClientLink(...values) {
    let parts = [];
    for (let value of values) {
        if (value.constructor === Object) {
            // assert this is the last piece
            let variableParts = [];
            for (let key of Object.keys(value)) {
                if (value[key]) {
                    variableParts.push(
                        key + '=' + escapeParam(value[key].toString()));
                }
            }
            if (variableParts.length) {
                parts.push(variableParts.join(';'));
            }
            break;
        } else {
            parts.push(escapeParam(value.toString()));
        }
    }
    return parts.join('/');
}

module.exports = {
    formatClientLink: formatClientLink,
    formatApiLink:    formatApiLink,
    escapeParam:      escapeParam,
    unescapeParam:    unescapeParam,
};
