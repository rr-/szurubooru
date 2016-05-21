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

function getVersion() {
    return execSync('git describe --always --dirty --long --tags').toString();
}

function getConfig() {
    const yaml = require('js-yaml');
    const merge = require('merge');
    const camelcaseKeys = require('camelcase-keys');

    function parseConfigFile(path) {
        let result = yaml.load(fs.readFileSync(path, 'utf-8'));
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
    const baseHtml = fs.readFileSync('./html/index.htm', 'utf-8');
    const finalHtml = baseHtml
        .replace(
            /(<title>)(.*)(<\/title>)/,
            util.format('$1%s$3', config.name));
    fs.writeFileSync('./public/index.htm', minifyHtml(finalHtml));

    glob('./html/**/*.tpl', {}, (er, files) => {
        let compiledTemplateJs = '\'use strict\'\n';
        compiledTemplateJs += 'let _ = require(\'underscore\');';
        compiledTemplateJs += 'let templates = {};';
        for (const file of files) {
            const name = path.basename(file, '.tpl').replace(/_/g, '-');
            const templateText = minifyHtml(fs.readFileSync(file, 'utf-8'));
            const functionText = underscore.template(
                templateText, {variable: 'ctx'}).source;
            compiledTemplateJs += `templates['${name}'] = ${functionText};`;
        }
        compiledTemplateJs += 'module.exports = templates;';
        fs.writeFileSync('./js/.templates.autogen.js', compiledTemplateJs);
        console.info('Bundled HTML');
    });
}

function bundleCss() {
    const stylus = require('stylus');
    glob('./css/**/*.styl', {}, (er, files) => {
        let css = '';
        for (const file of files) {
            css += stylus.render(
                fs.readFileSync(file, 'utf-8'), {filename: file});
        }
        fs.writeFileSync('./public/app.min.css', minifyCss(css));
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
        'page',
        'nprogress',
    ];

    function writeJsBundle(b, path, message, compress) {
        let outputFile = fs.createWriteStream(path);
        b.bundle().pipe(outputFile);
        outputFile.on('finish', function() {
            if (compress) {
                fs.writeFileSync(path, minifyJs(path));
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
                b, './public/vendor.min.js', 'Bundled vendor JS', true);
        }

        if (!process.argv.includes('--no-app-js')) {
            let outputFile = fs.createWriteStream('./public/app.min.js');
            let b = browserify({debug: config.debug});
            if (config.transpile) {
                b = b.transform('babelify');
            }
            writeJsBundle(
                b.external(external).add(files),
                './public/app.min.js',
                'Bundled app JS',
                !config.debug);
        }
    });
}

function bundleConfig(config) {
    fs.writeFileSync(
        './js/.config.autogen.json', JSON.stringify(config));
}

function copyFile(source, target) {
    fs.createReadStream(source).pipe(fs.createWriteStream(target));
}

const config = getConfig();
bundleConfig(config);
if (!process.argv.includes('--no-html')) {
    bundleHtml(config);
}
if (!process.argv.includes('--no-css')) {
    bundleCss();
}
if (!process.argv.includes('--no-js')) {
    bundleJs(config);
}
copyFile('./img/favicon.png', './public/favicon.png');
