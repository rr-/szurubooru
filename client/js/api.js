"use strict";

const cookies = require("js-cookie");
const request = require("superagent");
const events = require("./events.js");
const progress = require("./util/progress.js");
const uri = require("./util/uri.js");

let fileTokens = {};
let remoteConfig = null;

class Api extends events.EventTarget {
    constructor() {
        super();
        this.user = null;
        this.userName = null;
        this.userPassword = null;
        this.token = null;
        this.cache = {};
        this.allRanks = [
            "anonymous",
            "restricted",
            "regular",
            "power",
            "moderator",
            "administrator",
            "nobody",
        ];
        this.rankNames = new Map([
            ["anonymous", "Anonymous"],
            ["restricted", "Restricted user"],
            ["regular", "Regular user"],
            ["power", "Power user"],
            ["moderator", "Moderator"],
            ["administrator", "Administrator"],
            ["nobody", "Nobody"],
        ]);
    }

    get(url, options) {
        if (url in this.cache) {
            return new Promise((resolve, reject) => {
                resolve(this.cache[url]);
            });
        }
        return this._wrappedRequest(url, request.get, {}, {}, options).then(
            (response) => {
                this.cache[url] = response;
                return Promise.resolve(response);
            }
        );
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

    fetchConfig() {
        if (remoteConfig === null) {
            return this.get(uri.formatApiLink("info")).then((response) => {
                remoteConfig = response.config;
            });
        } else {
            return Promise.resolve();
        }
    }

    getName() {
        return remoteConfig.name;
    }

    getTagNameRegex() {
        return remoteConfig.tagNameRegex;
    }

    getPoolNameRegex() {
        return remoteConfig.poolNameRegex;
    }

    getPasswordRegex() {
        return remoteConfig.passwordRegex;
    }

    getUserNameRegex() {
        return remoteConfig.userNameRegex;
    }

    getContactEmail() {
        return remoteConfig.contactEmail;
    }

    canSendMails() {
        return !!remoteConfig.canSendMails;
    }

    safetyEnabled() {
        return !!remoteConfig.enableSafety;
    }

    hasPrivilege(lookup) {
        let minViableRank = null;
        for (let p of Object.keys(remoteConfig.privileges)) {
            if (!p.startsWith(lookup)) {
                continue;
            }
            const rankIndex = this.allRanks.indexOf(
                remoteConfig.privileges[p]
            );
            if (minViableRank === null || rankIndex < minViableRank) {
                minViableRank = rankIndex;
            }
        }
        if (minViableRank === null) {
            throw `Bad privilege name: ${lookup}`;
        }
        let myRank =
            this.user !== null ? this.allRanks.indexOf(this.user.rank) : 0;
        return myRank >= minViableRank;
    }

    loginFromCookies() {
        const auth = cookies.getJSON("auth");
        return auth && auth.user && auth.token
            ? this.loginWithToken(auth.user, auth.token, true)
            : Promise.resolve();
    }

    loginWithToken(userName, token, doRemember) {
        this.cache = {};
        return new Promise((resolve, reject) => {
            this.userName = userName;
            this.token = token;
            this.get("/user/" + userName + "?bump-login=true").then(
                (response) => {
                    const options = {};
                    if (doRemember) {
                        options.expires = 365;
                    }
                    cookies.set(
                        "auth",
                        { user: userName, token: token },
                        options
                    );
                    this.user = response;
                    resolve();
                    this.dispatchEvent(new CustomEvent("login"));
                },
                (error) => {
                    reject(error);
                    this.logout();
                }
            );
        });
    }

    createToken(userName, options) {
        let userTokenRequest = {
            enabled: true,
            note: "Web Login Token",
        };
        if (typeof options.expires !== "undefined") {
            userTokenRequest.expirationTime = new Date()
                .addDays(options.expires)
                .toISOString();
        }
        return new Promise((resolve, reject) => {
            this.post("/user-token/" + userName, userTokenRequest).then(
                (response) => {
                    cookies.set(
                        "auth",
                        { user: userName, token: response.token },
                        options
                    );
                    this.userName = userName;
                    this.token = response.token;
                    this.userPassword = null;
                },
                (error) => {
                    reject(error);
                }
            );
        });
    }

    deleteToken(userName, userToken) {
        return new Promise((resolve, reject) => {
            this.delete("/user-token/" + userName + "/" + userToken, {}).then(
                (response) => {
                    const options = {};
                    cookies.set(
                        "auth",
                        { user: userName, token: null },
                        options
                    );
                    resolve();
                },
                (error) => {
                    reject(error);
                }
            );
        });
    }

    login(userName, userPassword, doRemember) {
        this.cache = {};
        return new Promise((resolve, reject) => {
            this.userName = userName;
            this.userPassword = userPassword;
            this.get("/user/" + userName + "?bump-login=true").then(
                (response) => {
                    const options = {};
                    if (doRemember) {
                        options.expires = 365;
                    }
                    this.createToken(this.userName, options);
                    this.user = response;
                    resolve();
                    this.dispatchEvent(new CustomEvent("login"));
                },
                (error) => {
                    reject(error);
                    this.logout();
                }
            );
        });
    }

    logout() {
        let self = this;
        this.deleteToken(this.userName, this.token).then(
            (response) => {
                self._logout();
            },
            (error) => {
                self._logout();
            }
        );
    }

    _logout() {
        this.user = null;
        this.userName = null;
        this.userPassword = null;
        this.token = null;
        this.dispatchEvent(new CustomEvent("logout"));
    }

    forget() {
        cookies.remove("auth");
    }

    isLoggedIn(user) {
        if (user) {
            return (
                this.userName !== null &&
                this.userName.toLowerCase() === user.name.toLowerCase()
            );
        } else {
            return this.userName !== null;
        }
    }

    isCurrentAuthToken(userToken) {
        return userToken.token === this.token;
    }

    _getFullUrl(url) {
        const fullUrl = ("api/" + url).replace(/([^:])\/+/g, "$1/");
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
        let fileData = {};
        let abortFunction = () => {};
        let promise = Promise.resolve();
        if (files) {
            for (let key of Object.keys(files)) {
                const file = files[key];
                if (file === null) {
                    fileData[key] = null;
                    continue;
                }
                const fileId = this._getFileId(file);
                if (fileTokens[fileId]) {
                    data[key + "Token"] = fileTokens[fileId];
                } else {
                    promise = promise
                        .then(() => {
                            let uploadPromise = this._upload(file);
                            abortFunction = () => uploadPromise.abort();
                            return uploadPromise;
                        })
                        .then((token) => {
                            abortFunction = () => {};
                            fileTokens[fileId] = token;
                            data[key + "Token"] = token;
                            return Promise.resolve();
                        });
                }
            }
        }
        promise = promise
            .then(() => {
                let requestPromise = this._rawRequest(
                    url,
                    requestFactory,
                    data,
                    fileData,
                    options
                );
                abortFunction = () => requestPromise.abort();
                return requestPromise;
            })
            .catch((error) => {
                if (
                    error.response &&
                    error.response.name === "MissingOrExpiredRequiredFileError"
                ) {
                    for (let key of Object.keys(files)) {
                        const file = files[key];
                        const fileId = this._getFileId(file);
                        fileTokens[fileId] = null;
                    }
                    error.message =
                        "The uploaded file has expired; " +
                        "please resend the form to reupload.";
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
                "uploads",
                request.post,
                {},
                { content: file },
                options
            );
            abortFunction = () => uploadPromise.abort();
            return uploadPromise.then((response) => {
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

            req.set("Accept", "application/json");

            if (query) {
                req.query(query);
            }

            if (files) {
                for (let key of Object.keys(files)) {
                    const value = files[key];
                    if (value !== null && value.constructor === String) {
                        data[key + "Url"] = value;
                    } else {
                        req.attach(key, value || new Blob());
                    }
                }
            }

            if (data) {
                if (files && Object.keys(files).length) {
                    req.attach("metadata", new Blob([JSON.stringify(data)]));
                } else {
                    req.set("Content-Type", "application/json");
                    req.send(data);
                }
            }

            try {
                if (this.userName && this.token) {
                    req.auth = null;
                    // eslint-disable-next-line no-undef
                    req.set(
                        "Authorization",
                        "Token " +
                            new Buffer(
                                this.userName + ":" + this.token
                            ).toString("base64")
                    );
                } else if (this.userName && this.userPassword) {
                    req.auth(
                        this.userName,
                        encodeURIComponent(this.userPassword).replace(
                            /%([0-9A-F]{2})/g,
                            (match, p1) => {
                                return String.fromCharCode("0x" + p1);
                            }
                        )
                    );
                }
            } catch (e) {
                reject(
                    new Error("Authentication error (malformed credentials)")
                );
            }

            if (!options.noProgress) {
                progress.start();
            }

            abortFunction = () => {
                req.abort(); // does *NOT* call the callback passed in .end()
                progress.done();
                reject(
                    new Error("The request was aborted due to user cancel.")
                );
            };

            req.end((error, response) => {
                progress.done();
                abortFunction = () => {};
                if (error) {
                    if (response && response.body) {
                        error = new Error(
                            response.body.description || "Unknown error"
                        );
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
