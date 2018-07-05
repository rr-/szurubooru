'use strict';

const fs = require('fs');
const glob = require('glob');
const path = require('path');
const util = require('util');
const execSync = require('child_process').execSync;

function readTextFile(path) {
    return fs.readFileSync(path, 'utf-8');
}

function writeFile(path, content) {
    return fs.writeFileSync(path, content);
}

function getVersion() {
    let build_info = process.env.BUILD_INFO;
    if (build_info) {
        return build_info.trim();
    } else {
        try {
            build_info = execSync('git describe --always --dirty --long --tags')
                .toString();
        } catch (e) {
            console.warn('Cannot find build version');
            return 'unknown';
        }
        return build_info.trim();
    }
}

function getConfig() {
    let config = {
        meta: {
          version: getVersion(),
          buildDate: new Date().toUTCString()
        }
    };

    return config;
}

function copyFile(source, target) {
    fs.createReadStream(source).pipe(fs.createWriteStream(target));
}

function minifyJs(path) {
    return require('uglify-es').minify(fs.readFileSync(path, 'utf-8'), {compress: {unused: false}}).code;
}

function minifyCss(css) {
    return require('csso').minify(css);
}

function minifyHtml(html) {
    return require('html-minifier').minify(html, {
        removeComments: true,
        collapseWhitespace: true,
        conservativeCollapse: true,
    }).trim();
}

function bundleHtml() {
    const underscore = require('underscore');
    const babelify = require('babelify');
    const baseHtml = readTextFile('./html/index.htm', 'utf-8');
    writeFile('./public/index.htm', minifyHtml(baseHtml));

    glob('./html/**/*.tpl', {}, (er, files) => {
        let compiledTemplateJs = '\'use strict\'\n';
        compiledTemplateJs += 'let _ = require(\'underscore\');';
        compiledTemplateJs += 'let templates = {};';
        for (const file of files) {
            const name = path.basename(file, '.tpl').replace(/_/g, '-');
            const placeholders = [];
            let templateText = readTextFile(file, 'utf-8');
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
                templateText, {variable: 'ctx'}).source;
            compiledTemplateJs += `templates['${name}'] = ${functionText};`;
        }
        compiledTemplateJs += 'module.exports = templates;';
        writeFile('./js/.templates.autogen.js', compiledTemplateJs);
        console.info('Bundled HTML');
    });
}

function bundleCss() {
    const stylus = require('stylus');
    glob('./css/**/*.styl', {}, (er, files) => {
        let css = '';
        for (const file of files) {
            css += stylus.render(
                readTextFile(file), {filename: file});
        }
        writeFile('./public/css/app.min.css', minifyCss(css));

        copyFile(
            './node_modules/font-awesome/css/font-awesome.min.css',
            './public/css/vendor.min.css');

        console.info('Bundled CSS');
    });
}

function bundleJs() {
    const browserify = require('browserify');
    const external = [
        'underscore',
        'superagent',
        'mousetrap',
        'js-cookie',
        'nprogress',
    ];

    function writeJsBundle(b, path, message, compress) {
        let outputFile = fs.createWriteStream(path);
        b.bundle().pipe(outputFile);
        outputFile.on('finish', function() {
            if (compress) {
                writeFile(path, minifyJs(path));
            }
            console.info(message);
        });
    }

    glob('./js/**/*.js', {}, (er, files) => {
        if (!process.argv.includes('--no-vendor-js')) {
            let b = browserify();
            for (let lib of external) {
                b.require(lib);
            }
            if (!process.argv.includes('--no-transpile')) {
                b.add(require.resolve('babel-polyfill'));
            }
            writeJsBundle(
                b, './public/js/vendor.min.js', 'Bundled vendor JS', true);
        }

        if (!process.argv.includes('--no-app-js')) {
            let outputFile = fs.createWriteStream('./public/js/app.min.js');
            let b = browserify({debug: process.argv.includes('--debug')});
            if (!process.argv.includes('--no-transpile')) {
                b = b.transform('babelify');
            }
            writeJsBundle(
                b.external(external).add(files),
                './public/js/app.min.js',
                'Bundled app JS',
                !process.argv.includes('--debug'));
        }
    });
}

function bundleConfig(config) {
    writeFile(
        './js/.config.autogen.json', JSON.stringify(config));
    glob('./node_modules/font-awesome/fonts/*.*', {}, (er, files) => {
        for (let file of files) {
            if (fs.lstatSync(file).isDirectory()) {
                continue;
            }
            copyFile(file, path.join('./public/fonts/', path.basename(file)));
        }
    });
}

function bundleBinaryAssets() {
    glob('./img/*.png', {}, (er, files) => {
        for (let file of files) {
            copyFile(file, path.join('./public/img/', path.basename(file)));
        }
    });
}

const config = getConfig();
bundleConfig(config);
bundleBinaryAssets();
if (!process.argv.includes('--no-html')) {
    bundleHtml();
}
if (!process.argv.includes('--no-css')) {
    bundleCss();
}
if (!process.argv.includes('--no-js')) {
    bundleJs();
}
