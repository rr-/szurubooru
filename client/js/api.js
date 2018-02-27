'use strict';

const cookies = require('js-cookie');
const request = require('superagent');
const config = require('./config.js');
const events = require('./events.js');
const progress = require('./util/progress.js');
const uri = require('./util/uri.js');

let fileTokens = {};

class Api extends events.EventTarget {
    constructor() {
        super();
        this.user = null;
        this.userName = null;
        this.userPassword = null;
        this.userToken = null;
        this.cache = {};
        this.allRanks = [
            'anonymous',
            'restricted',
            'regular',
            'power',
            'moderator',
            'administrator',
            'nobody',
        ];
        this.rankNames = new Map([
            ['anonymous', 'Anonymous'],
            ['restricted', 'Restricted user'],
            ['regular', 'Regular user'],
            ['power', 'Power user'],
            ['moderator', 'Moderator'],
            ['administrator', 'Administrator'],
            ['nobody', 'Nobody'],
        ]);
    }

    get(url, options) {
        if (url in this.cache) {
            return new Promise((resolve, reject) => {
                resolve(this.cache[url]);
            });
        }
        return this._wrappedRequest(url, request.get, {}, {}, options)
            .then(response => {
                this.cache[url] = response;
                return Promise.resolve(response);
            });
    }

    post(url, data, files, options) {
        this.cache = {};
        return this._wrappedRequest(url, request.post, data, files, options);
    }

    put(url, data, files, options) {
        this.cache = {};
        return this._wrappedRequest(url, request.put, data, files, options);
    }

    delete(url, data, options) {
        this.cache = {};
        return this._wrappedRequest(url, request.delete, data, {}, options);
    }

    hasPrivilege(lookup) {
        let minViableRank = null;
        for (let privilege of Object.keys(config.privileges)) {
            if (!privilege.startsWith(lookup)) {
                continue;
            }
            const rankName = config.privileges[privilege];
            const rankIndex = this.allRanks.indexOf(rankName);
            if (minViableRank === null || rankIndex < minViableRank) {
                minViableRank = rankIndex;
            }
        }
        if (minViableRank === null) {
            throw `Bad privilege name: ${lookup}`;
        }
        let myRank = this.user !== null ?
            this.allRanks.indexOf(this.user.rank) :
            0;
        return myRank >= minViableRank;
    }

    loginFromCookies() {
        const auth = cookies.getJSON('auth');
        return auth && auth.user && auth.token ?
            this.login_with_token(auth.user, auth.token, true) :
            Promise.resolve();
    }

    login_with_token(userName, token, doRemember) {
        this.cache = {};
        return new Promise((resolve, reject) => {
            this.userName = userName;
            this.userToken = token;
            this.get('/user/' + userName + '?bump-login=true')
                .then(response => {
                    const options = {};
                    if (doRemember) {
                        options.expires = 365;
                    }
                    cookies.set(
                        'auth',
                        {'user': userName, 'token': token},
                        options);
                    this.user = response;
                    resolve();
                    this.dispatchEvent(new CustomEvent('login'));
                }, error => {
                    reject(error);
                    this.logout();
                });
        });
    }

    get_token(userName, options) {
        return new Promise((resolve, reject) => {
            this.post('/user-tokens', {})
                .then(response => {
                    cookies.set(
                        'auth',
                        {'user': userName, 'token': response.token},
                        options);
                    this.userName = userName;
                    this.userToken = response.token;
                    this.userPassword = null;
                }, error => {
                    reject(error);
                });
        });
    }

    delete_token(userName, userToken) {
        return new Promise((resolve, reject) => {
            this.delete('/user-tokens/' + userToken, {})
                .then(response => {
                    const options = {};
                    cookies.set(
                        'auth',
                        {'user': userName, 'token': null},
                        options);
                    this.userName = userName;
                    this.userToken = null;
                }, error => {
                    reject(error);
                });
        });
    }

    login(userName, userPassword, doRemember) {
        this.cache = {};
        return new Promise((resolve, reject) => {
            this.userName = userName;
            this.userPassword = userPassword;
            this.get('/user/' + userName + '?bump-login=true')
                .then(response => {
                    const options = {};
                    if (doRemember) {
                        options.expires = 365;
                    }
                    this.get_token(this.userName, options);
                    this.user = response;
                    resolve();
                    this.dispatchEvent(new CustomEvent('login'));
                }, error => {
                    reject(error);
                    this.logout();
                });
        });
    }

    logout() {
        this.delete_token(this.userName, this.userToken).then(response => {
            this._logout()
        }, error => {
            this._logout()
        });

    }

    _logout() {
        this.user = null;
        this.userName = null;
        this.userPassword = null;
        this.dispatchEvent(new CustomEvent('logout'));
    }

    forget() {
        cookies.remove('auth');
    }

    isLoggedIn(user) {
        if (user) {
            return this.userName !== null &&
                this.userName.toLowerCase() === user.name.toLowerCase();
        } else {
            return this.userName !== null;
        }
    }

    _getFullUrl(url) {
        const fullUrl =
            (config.apiUrl + '/' + url).replace(/([^:])\/+/g, '$1/');
        const matches = fullUrl.match(/^([^?]*)\??(.*)$/);
        const baseUrl = matches[1];
        const request = matches[2];
        return [baseUrl, request];
    }

    _getFileId(file) {
        if (file.constructor === String) {
            return file;
        }
        return file.name + file.size;
    }

    _wrappedRequest(url, requestFactory, data, files, options) {
        // transform the request: upload each file, then make the request use
        // its tokens.
        data = Object.assign({}, data);
        let abortFunction = () => {};
        let promise = Promise.resolve();
        if (files) {
            for (let key of Object.keys(files)) {
                const file = files[key];
                const fileId = this._getFileId(file);
                if (fileTokens[fileId]) {
                    data[key + 'Token'] = fileTokens[fileId];
                } else {
                    promise = promise
                        .then(() => {
                            let uploadPromise = this._upload(file);
                            abortFunction = () => uploadPromise.abort();
                            return uploadPromise;
                        })
                        .then(token => {
                            abortFunction = () => {};
                            fileTokens[fileId] = token;
                            data[key + 'Token'] = token;
                            return Promise.resolve();
                        });
                }
            }
        }
        promise = promise.then(
            () => {
                let requestPromise = this._rawRequest(
                    url, requestFactory, data, {}, options);
                abortFunction = () => requestPromise.abort();
                return requestPromise;
            })
            .catch(error => {
                if (error.response && error.response.name ===
                        'MissingOrExpiredRequiredFileError') {
                    for (let key of Object.keys(files)) {
                        const file = files[key];
                        const fileId = this._getFileId(file);
                        fileTokens[fileId] = null;
                    }
                    error.message =
                        'The uploaded file has expired; ' +
                        'please resend the form to reupload.';
                }
                return Promise.reject(error);
            });
        promise.abort = () => abortFunction();
        return promise;
    }

    _upload(file, options) {
        let abortFunction = () => {};
        let returnedPromise = new Promise((resolve, reject) => {
            let uploadPromise = this._rawRequest(
                '/uploads', request.post, {}, {content: file}, options);
            abortFunction = () => uploadPromise.abort();
            return uploadPromise.then(
                response => {
                    abortFunction = () => {};
                    return resolve(response.token);
                }, reject);
        });
        returnedPromise.abort = () => abortFunction();
        return returnedPromise;
    }

    _rawRequest(url, requestFactory, data, files, options) {
        options = options || {};
        data = Object.assign({}, data);
        const [fullUrl, query] = this._getFullUrl(url);

        let abortFunction = () => {};
        let returnedPromise = new Promise((resolve, reject) => {
            let req = requestFactory(fullUrl);

            req.set('Accept', 'application/json');

            if (query) {
                req.query(query);
            }

            if (files) {
                for (let key of Object.keys(files)) {
                    const value = files[key];
                    if (value.constructor === String) {
                        data[key + 'Url'] = value;
                    } else {
                        req.attach(key, value || new Blob());
                    }
                }
            }

            if (data) {
                if (files && Object.keys(files).length) {
                    req.attach('metadata', new Blob([JSON.stringify(data)]));
                } else {
                    req.set('Content-Type', 'application/json');
                    req.send(data);
                }
            }

            try {
                if (this.userName && this.userToken) {
                    req.auth = null;
                    req.set('Authorization', 'Token ' + new Buffer(this.userName + ":" + this.userToken).toString('base64'))
                }
                else if (this.userName && this.userPassword) {
                    req.auth(
                        this.userName,
                        encodeURIComponent(this.userPassword)
                            .replace(/%([0-9A-F]{2})/g, (match, p1) => {
                                return String.fromCharCode('0x' + p1);
                            }));
                }
            } catch (e) {
                reject(
                    new Error('Authentication error (malformed credentials)'));
            }

            if (!options.noProgress) {
                progress.start();
            }

            abortFunction = () => {
                req.abort();  // does *NOT* call the callback passed in .end()
                progress.done();
                reject(
                    new Error('The request was aborted due to user cancel.'));
            };

            req.end((error, response) => {
                progress.done();
                abortFunction = () => {};
                if (error) {
                    if (response && response.body) {
                        error = new Error(
                            response.body.description || 'Unknown error');
                        error.response = response.body;
                    }
                    reject(error);
                } else {
                    resolve(response.body);
                }
            });
        });
        returnedPromise.abort = () => abortFunction();
        return returnedPromise;
    }
}

module.exports = new Api();
