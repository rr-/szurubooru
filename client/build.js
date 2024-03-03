#!/usr/bin/env node
'use strict';

// -------------------------------------------------

const webapp_icons = [
    { name: 'android-chrome-192x192.png', size: 192 },
    { name: 'android-chrome-512x512.png', size: 512 },
    { name: 'apple-touch-icon.png', size: 180 },
    { name: 'mstile-150x150.png', size: 150 }
];

const webapp_splash_screens = [
    { w: 640, h: 1136, center: 320 },
    { w: 750, h: 1294, center: 375 },
    { w: 1125, h: 2436, center: 565 },
    { w: 1242, h: 2148, center: 625 },
    { w: 1536, h: 2048, center: 770 },
    { w: 1668, h: 2224, center: 820 },
    { w: 2048, h: 2732, center: 1024 }
];

const external_js = [
    'dompurify',
    'js-cookie',
    'marked',
    'mousetrap',
    'nprogress',
    'superagent',
    'underscore',
];

const app_manifest = {
    name: 'szurubooru',
    icons: [
        {
            src: baseUrl() + 'img/android-chrome-192x192.png',
            type: 'image/png',
            sizes: '192x192'
        },
        {
            src: baseUrl() + 'img/android-chrome-512x512.png',
            type: 'image/png',
            sizes: '512x512'
        }
    ],
    start_url: baseUrl(),
    theme_color: '#24aadd',
    background_color: '#ffffff',
    display: 'standalone'
}

// -------------------------------------------------

const fs = require('fs');
const glob = require('glob');
const path = require('path');
const util = require('util');
const execSync = require('child_process').execSync;
const browserify = require('browserify');
const chokidar = require('chokidar');
const WebSocket = require('ws');
var PrettyError = require('pretty-error');
var pe = new PrettyError();

function readTextFile(path) {
    return fs.readFileSync(path, 'utf-8');
}

function gzipFile(file) {
    file = path.normalize(file);
    execSync('gzip -6 -k ' + file);
}

function baseUrl() {
    return process.env.BASE_URL ? process.env.BASE_URL : '/';
}

// -------------------------------------------------

function bundleHtml() {
    const underscore = require('underscore');
    const babelify = require('babelify');

    function minifyHtml(html) {
        return require('html-minifier').minify(html, {
            removeComments: false,
            collapseWhitespace: true,
            conservativeCollapse: true,
        }).trim();
    }

    const baseHtml = readTextFile('./html/index.htm')
        .replace('<!-- Base HTML Placeholder -->', `<base href="${baseUrl()}"/>`);
    fs.writeFileSync('./public/index.htm', minifyHtml(baseHtml));

    let compiledTemplateJs = [
        `'use strict';`,
        `let _ = require('underscore');`,
        `let templates = {};`
    ];

    for (const file of glob.sync('./html/**/*.tpl')) {
        const name = path.basename(file, '.tpl').replace(/_/g, '-');
        const placeholders = [];
        let templateText = readTextFile(file);
        templateText = templateText.replace(
            /<%.*?%>/ig,
            (match) => {
                const ret = '%%%TEMPLATE' + placeholders.length;
                placeholders.push(match);
                return ret;
            });
        templateText = minifyHtml(templateText);
        templateText = templateText.replace(
            /%%%TEMPLATE(\d+)/g,
            (match, number) => { return placeholders[number]; });

        const functionText = underscore.template(
            templateText, { variable: 'ctx' }).source;

        compiledTemplateJs.push(`templates['${name}'] = ${functionText};`);
    }
    compiledTemplateJs.push('module.exports = templates;');

    fs.writeFileSync('./js/.templates.autogen.js', compiledTemplateJs.join('\n'));
    console.info('Bundled HTML');
}

function bundleCss() {
    const stylus = require('stylus');

    function minifyCss(css) {
        return require('csso').minify(css).css;
    }

    let css = '';
    for (const file of glob.sync('./css/**/*.styl')) {
        css += stylus.render(readTextFile(file), { filename: file });
    }
    fs.writeFileSync('./public/css/app.min.css', minifyCss(css));
    if (process.argv.includes('--gzip')) {
        gzipFile('./public/css/app.min.css');
    }

    fs.copyFileSync(
        './node_modules/font-awesome/css/font-awesome.min.css',
        './public/css/vendor.min.css');
    if (process.argv.includes('--gzip')) {
        gzipFile('./public/css/vendor.min.css');
    }

    console.info('Bundled CSS');
}

function minifyJs(path) {
    return require('terser').minify(
        fs.readFileSync(path, 'utf-8'), { compress: { unused: false } }).code;
}

function writeJsBundle(b, path, compress, callback) {
    let outputFile = fs.createWriteStream(path);
    b.bundle().on('error', (e) => console.error(pe.render(e))).pipe(outputFile);
    outputFile.on('finish', () => {
        if (compress) {
            fs.writeFileSync(path, minifyJs(path));
        }
        callback();
    });
}

function bundleVendorJs(compress) {
    let b = browserify();
    for (let lib of external_js) {
        b.require(lib);
    }
    if (!process.argv.includes('--no-transpile')) {
        b.add(require.resolve('babel-polyfill'));
    }
    const file = './public/js/vendor.min.js';
    writeJsBundle(b, file, compress, () => {
        if (process.argv.includes('--gzip')) {
            gzipFile(file);
        }
        console.info('Bundled vendor JS');
    });
}

function bundleAppJs(b, compress, callback) {
    const file = './public/js/app.min.js';
    writeJsBundle(b, file, compress, () => {
        if (process.argv.includes('--gzip')) {
            gzipFile(file);
        }
        console.info('Bundled app JS');
        callback();
    });
}

function bundleJs() {
    if (!process.argv.includes('--no-vendor-js')) {
        bundleVendorJs(true);
    }

    if (!process.argv.includes('--no-app-js')) {
        let watchify = require('watchify');
        let b = browserify({ debug: process.argv.includes('--debug') });
        if (!process.argv.includes('--no-transpile')) {
            b = b.transform('babelify');
        }
        b = b.external(external_js).add(glob.sync('./js/**/*.js'));
        const compress = !process.argv.includes('--debug');
        bundleAppJs(b, compress, () => { });
    }
}

const environment = process.argv.includes('--watch') ? "development" : "production";

function bundleConfig() {
    function getVersion() {
        let build_info = process.env.BUILD_INFO;
        if (!build_info) {
            try {
                build_info = execSync('git describe --always --dirty --long --tags').toString();
            } catch (e) {
                console.warn('Cannot find build version');
                build_info = 'unknown';
            }
        }
        return build_info.trim();
    }
    const config = {
        meta: {
            version: getVersion(),
            buildDate: new Date().toUTCString()
        },
        environment: environment
    };

    fs.writeFileSync('./js/.config.autogen.json', JSON.stringify(config));
    console.info('Generated config file');
}

function bundleBinaryAssets() {
    fs.copyFileSync('./img/favicon.png', './public/img/favicon.png');
    console.info('Copied images');

    fs.copyFileSync('./fonts/open_sans.woff2', './public/fonts/open_sans.woff2')
    for (let file of glob.sync('./node_modules/font-awesome/fonts/*.*')) {
        if (fs.lstatSync(file).isDirectory()) {
            continue;
        }
        fs.copyFileSync(file, path.join('./public/fonts/', path.basename(file)));
    }
    if (process.argv.includes('--gzip')) {
        for (let file of glob.sync('./public/fonts/*.*')) {
            if (file.endsWith('woff2')) {
                continue;
            }
            gzipFile(file);
        }
    }
    console.info('Copied fonts')
}

function bundleWebAppFiles() {
    const Jimp = require('jimp');

    fs.writeFileSync('./public/manifest.json', JSON.stringify(app_manifest));
    console.info('Generated app manifest');

    Promise.all(webapp_icons.map(icon => {
        return Jimp.read('./img/app.png')
            .then(file => {
                file.resize(icon.size, Jimp.AUTO, Jimp.RESIZE_BEZIER)
                    .write(path.join('./public/img/', icon.name));
            });
    }))
        .then(() => {
            console.info('Generated webapp icons');
        });

    Promise.all(webapp_splash_screens.map(dim => {
        return Jimp.read('./img/splash.png')
            .then(file => {
                file.resize(dim.center, Jimp.AUTO, Jimp.RESIZE_BEZIER)
                    .background(0xFFFFFFFF)
                    .contain(dim.w, dim.center,
                        Jimp.HORIZONTAL_ALIGN_CENTER | Jimp.VERTICAL_ALIGN_MIDDLE)
                    .contain(dim.w, dim.h,
                        Jimp.HORIZONTAL_ALIGN_CENTER | Jimp.VERTICAL_ALIGN_MIDDLE)
                    .write(path.join('./public/img/',
                        'apple-touch-startup-image-' + dim.w + 'x' + dim.h + '.png'));
            });
    }))
        .then(() => {
            console.info('Generated splash screens');
        });
}

function makeOutputDirs() {
    const dirs = [
        './public',
        './public/css',
        './public/fonts',
        './public/img',
        './public/js'
    ];
    for (let dir of dirs) {
        if (!fs.existsSync(dir)) {
            fs.mkdirSync(dir, 0o755);
            console.info('Created directory: ' + dir);
        }
    }
}

function watch() {
    let wss = new WebSocket.Server({ port: 8080 });
    const liveReload = !process.argv.includes('--no-live-reload');

    function emitReload() {
        if (liveReload) {
            console.log("Requesting live reload.")
            wss.clients.forEach((client) => {
                if (client.readyState === WebSocket.OPEN) {
                    client.send("reload");
                }
            });
        }
    }

    chokidar.watch('./fonts/**/*').on('change', () => {
        try {
            bundleBinaryAssets();
            emitReload();
        } catch (e) {
            console.error(pe.render(e));
        }
    });
    chokidar.watch('./img/**/*').on('change', () => {
        try {
            bundleWebAppFiles();
            emitReload();
        } catch (e) {
            console.error(pe.render(e));
        }
    });
    chokidar.watch('./html/**/*.tpl').on('change', () => {
        try {
            bundleHtml();
        } catch (e) {
            console.error(pe.render(e));
        }
    });
    chokidar.watch('./css/**/*.styl').on('change', () => {
        try {
            bundleCss()
            emitReload();
        } catch (e) {
            console.error(pe.render(e));
        }
    });

    bundleBinaryAssets();
    bundleWebAppFiles();
    bundleCss();
    bundleHtml();

    bundleVendorJs(true);

    let watchify = require('watchify');
    let b = browserify({
        debug: process.argv.includes('--debug'),
        entries: ['js/main.js'],
        cache: {},
        packageCache: {},
    });

    b.plugin(watchify);

    if (!process.argv.includes('--no-transpile')) {
        b = b.transform('babelify');
    }
    b = b.external(external_js).add(glob.sync('./js/**/*.js'));
    const compress = false;

    function bundle(id) {
        console.info("Rebundling app JS...");
        let start = new Date();
        bundleAppJs(b, compress, () => {
            let end = new Date() - start;
            console.info('Rebundled in %ds.', end / 1000)
            emitReload();
        });
    }

    b.on('update', bundle);
    bundle();
}

// -------------------------------------------------

console.log("Building for '" + environment + "' environment.");
makeOutputDirs();
bundleConfig();
if (process.argv.includes('--watch')) {
    watch();
} else {
    if (!process.argv.includes('--no-binary-assets')) {
        bundleBinaryAssets();
    }
    if (!process.argv.includes('--no-web-app-files')) {
        bundleWebAppFiles();
    }
    if (!process.argv.includes('--no-html')) {
        bundleHtml();
    }
    if (!process.argv.includes('--no-css')) {
        bundleCss();
    }
    if (!process.argv.includes('--no-js')) {
        bundleJs();
    }
}
