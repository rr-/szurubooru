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

function bundleHtml(config) {
    const minify = require('html-minifier').minify;
    const baseHtml = fs.readFileSync('./html/index.htm', 'utf-8');
    const minifyOptions = {
        removeComments: true,
        collapseWhitespace: true,
        conservativeCollapse: true,
    };
    glob('./html/**/*.hbs', {}, (er, files) => {
        let templates = {};
        for (const file of files) {
            const name = path.basename(file, '.hbs').replace(/_/g, '-');
            templates[name] = minify(
                fs.readFileSync(file, 'utf-8'), minifyOptions);
        }

        const templatesHolder = util.format(
            '<script type=\'text/javascript\'>' +
            'const templates = %s;' +
            '</script>',
            JSON.stringify(templates));

        const finalHtml = baseHtml
            .replace(/(<\/head>)/, templatesHolder + '$1')
            .replace(
                /(<title>)(.*)(<\/title>)/,
                util.format('$1%s$3', config.name));

        fs.writeFileSync(
            './public/index.htm', minify(finalHtml, minifyOptions));
        console.info('Bundled HTML');
    });
}

function bundleCss() {
    const minify = require('csso').minify;
    const stylus = require('stylus');
    glob('./css/**/*.styl', {}, (er, files) => {
        let css = '';
        for (const file of files) {
            css += stylus.render(
                fs.readFileSync(file, 'utf-8'), {filename: file});
        }
        fs.writeFileSync('./public/bundle.min.css', minify(css));
        console.info('Bundled CSS');
    });
}

function bundleJs(config) {
    const babelify = require('babelify');
    const browserify = require('browserify');
    const uglifyjs = require('uglify-js');
    glob('./js/**/*.js', {}, function(er, files) {
        const outputFile = fs.createWriteStream('./public/bundle.min.js');
        let b = browserify({debug: config.debug});
        if (config.transpile) {
            b = b.transform(babelify);
        }
        b.add(files).bundle().pipe(outputFile);
        outputFile.on('finish', function() {
            if (!config.debug) {
                const result = uglifyjs.minify('./public/bundle.min.js');
                fs.writeFileSync('./public/bundle.min.js', result.code);
            }
            console.info('Bundled JS');
        });
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
bundleHtml(config);
bundleCss();
bundleJs(config);
copyFile('./img/favicon.png', './public/favicon.png');
