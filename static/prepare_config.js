'use strict';

const fs = require('fs');
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

fs.writeFileSync('./static/js/.config.autogen.json', JSON.stringify(config));
