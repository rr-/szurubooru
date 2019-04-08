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

function extractHostname(url) {
    // https://stackoverflow.com/a/23945027
    return url
        .split('/')[url.indexOf("//") > -1 ? 2 : 0]
        .split(':')[0]
        .split('?')[0];
}

function extractRootDomain(url) {
    // https://stackoverflow.com/a/23945027
    let domain = extractHostname(url);
    let splitArr = domain.split('.');
    let arrLen = splitArr.length;

    // if there is a subdomain
    if (arrLen > 2) {
        domain = splitArr[arrLen - 2] + '.' + splitArr[arrLen - 1];
        // check to see if it's using a Country Code Top Level Domain (ccTLD) (i.e. ".me.uk")
        if (splitArr[arrLen - 2].length == 2 && splitArr[arrLen - 1].length == 2) {
            // this is using a ccTLD
            domain = splitArr[arrLen - 3] + '.' + domain;
        }
    }
    return domain;
}

function escapeColons(text) {
    return text.replace(new RegExp(':', 'g'), '\\:');
}

module.exports = {
    formatClientLink:  formatClientLink,
    formatApiLink:     formatApiLink,
    escapeColons:      escapeColons,
    escapeParam:       escapeParam,
    unescapeParam:     unescapeParam,
    extractHostname:   extractHostname,
    extractRootDomain: extractRootDomain,
};
