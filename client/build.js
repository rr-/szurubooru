'use strict';

const fs = require('fs');
const glob = require('glob');
const path = require('path');
const util = require('util');
const execSync = require('child_process').execSync;
const camelcase = require('camelcase');

function convertKeysToCamelCase(input) {
    let result = {};
    Object.keys(input).map((key, _) => {
        const value = input[key];
        if (value !== null && value.constructor == Object) {
            result[camelcase(key)] = convertKeysToCamelCase(value);
        } else {
            result[camelcase(key)] = value;
        }
    });
    return result;
}

function readTextFile(path) {
    return fs.readFileSync(path, 'utf-8');
}

function writeFile(path, content) {
    return fs.writeFileSync(path, content);
}

function getVersion() {
    return execSync('git describe --always --dirty --long --tags')
        .toString()
        .trim();
}

function getConfig() {
    const yaml = require('js-yaml');
    const merge = require('merge');
    const camelcaseKeys = require('camelcase-keys');

    function parseConfigFile(path) {
        let result = yaml.load(readTextFile(path, 'utf-8'));
        return convertKeysToCamelCase(result);
    }

    let config = parseConfigFile('../config.yaml.dist');

    try {
        const localConfig = parseConfigFile('../config.yaml');
        config = merge.recursive(config, localConfig);
    } catch (e) {
        console.warn('Local config does not exist, ignoring');
    }

    config.canSendMails = !!config.smtp.host;
    delete config.secret;
    delete config.smtp;
    delete config.database;
    config.meta = {
        version: getVersion(),
        buildDate: new Date().toUTCString(),
    };

    return config;
}

function copyFile(source, target) {
    fs.createReadStream(source).pipe(fs.createWriteStream(target));
}

function minifyJs(path) {
    return require('uglify-js').minify(path).code;
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

function bundleHtml(config) {
    const underscore = require('underscore');
    const babelify = require('babelify');
    const baseHtml = readTextFile('./html/index.htm', 'utf-8');
    const finalHtml = baseHtml
        .replace(
            /(<title>)(.*)(<\/title>)/,
            util.format('$1%s$3', config.name));
    writeFile('./public/index.htm', minifyHtml(finalHtml));

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

function bundleJs(config) {
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
            if (config.transpile) {
                b.add(require.resolve('babel-polyfill'));
            }
            writeJsBundle(
                b, './public/js/vendor.min.js', 'Bundled vendor JS', true);
        }

        if (!process.argv.includes('--no-app-js')) {
            let outputFile = fs.createWriteStream('./public/js/app.min.js');
            let b = browserify({debug: config.debug});
            if (config.transpile) {
                b = b.transform('babelify');
            }
            writeJsBundle(
                b.external(external).add(files),
                './public/js/app.min.js',
                'Bundled app JS',
                !config.debug);
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

process.on('uncaughtException', (error) => {
    const stack = error.stack;
    delete error.stack;
    console.log(error);
    console.log(stack);
});

const config = getConfig();
bundleConfig(config);
bundleBinaryAssets();
if (!process.argv.includes('--no-html')) {
    bundleHtml(config);
}
if (!process.argv.includes('--no-css')) {
    bundleCss();
}
if (!process.argv.includes('--no-js')) {
    bundleJs(config);
}
