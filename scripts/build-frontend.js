'use strict';

const fs = require('fs');
const glob = require('glob');
const path = require('path');
const util = require('util');

function getConfig() {
    const ini = require('ini');
    const merge = require('merge');
    const camelcaseKeys = require('camelcase-keys');

    function parseIniFile(path) {
        let result = ini.parse(fs.readFileSync(path, 'utf-8')
            .replace(/#.+$/gm, '')
            .replace(/\s+$/gm, ''));
        Object.keys(result).map((key, _) => {
            result[key] = camelcaseKeys(result[key]);
        });
        return result;
    }

    let config = parseIniFile('./config.ini.dist');

    try {
        const localConfig = parseIniFile('./config.ini');
        config = merge.recursive(config, localConfig);
    } catch (e) {
        console.warn('Local config does not exist, ignoring');
    }

    delete config.basic.secret;
    delete config.smtp;
    delete config.database;
    config.service.userRanks = config.service.userRanks.split(/,\s*/);
    config.service.tagCategories = config.service.tagCategories.split(/,\s*/);

    return config;
}

function bundleHtml(config) {
    const minify = require('html-minifier').minify;
    const baseHtml = fs.readFileSync('./static/html/index.htm', 'utf-8');
    glob('static/html/**/*.tpl', {}, (er, files) => {
        let templatesHtml = '';
        for (const file of files) {
            templatesHtml += util.format(
                '<template id=\'%s-template\'>%s</template>',
                path.basename(file, '.tpl').replace('_', '-'),
                fs.readFileSync(file));
        }

        const finalHtml = baseHtml
            .replace(/(<\/head>)/, templatesHtml + '$1')
            .replace(
                /(<title>)(.*)(<\/title>)/,
                util.format('$1%s$3', config.basic.name));

        fs.writeFileSync(
            './public/index.htm',
            minify(
                finalHtml, {removeComments: true, collapseWhitespace: true}));
        console.info('Bundled HTML');
    });
}

function bundleCss() {
    const minify = require('cssmin');
    glob('static/css/**/*.css', {}, (er, files) => {
        let css = '';
        for (const file of files) {
            css += fs.readFileSync(file);
        }
        fs.writeFileSync('./public/bundle.min.css', minify(css));
        console.info('Bundled CSS');
    });
}

function bundleJs() {
    const browserify = require('browserify');
    const uglifyjs = require('uglify-js');
    glob('./static/js/**/*.js', {}, function(er, files) {
        const outputFile = fs.createWriteStream('./public/bundle.min.js');
        browserify().add(files).bundle().pipe(outputFile);
        outputFile.on('finish', function() {
            const result = uglifyjs.minify('./public/bundle.min.js');
            fs.writeFileSync('./public/bundle.min.js', result.code);
            console.info('Bundled JS');
        });
    });
}

function bundleConfig(config) {
    fs.writeFileSync(
        './static/js/.config.autogen.json', JSON.stringify(config));
}

const config = getConfig();
bundleConfig(config);
bundleHtml(config);
bundleCss();
bundleJs();
