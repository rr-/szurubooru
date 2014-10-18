var path = require('path');
var fs = require('fs');
var ini = require('ini');
var rmdir = require('rimraf');
require('shelljs/global');

var phpCheckStyleConfigPath = path.join(path.resolve(), 'phpcheckstyle.cfg');
var phpSourcesDir = path.join(path.resolve(), 'src');
var publicHtmlDir = path.join(path.resolve(), 'public_html');
var jsSourcesDir = path.join(publicHtmlDir, 'js');
var cssSourcesDir = path.join(publicHtmlDir, 'css');
var templatesDir = path.join(publicHtmlDir, 'templates');

var config = readConfig([
		path.join(path.resolve(), 'data/config.ini'),
		path.join(path.resolve(), 'data/local.ini')
	]);

function readConfig(configPaths) {
	var iniContent = '';
	for (var i = 0; i < configPaths.length; i ++) {
		var configPath = configPaths[i];
		if (fs.existsSync(configPath)) {
			iniContent += fs.readFileSync(configPath, 'utf-8');
		}
	}
	var config = ini.parse(iniContent);
	return config;
}

function readTemplates(grunt) {
	var templatePaths = grunt.file.expand(templatesDir + '/**/*.tpl');
	var templates = {};
	for (var i = 0; i < templatePaths.length; i ++) {
		var templatePath = templatePaths[i];
		templates[path.basename(templatePath).replace('.tpl', '')] = fs.readFileSync(templatePath);
	}
	return templates;
}

module.exports = function(grunt) {

	var pkg = grunt.file.readJSON('package.json');

	grunt.initConfig({
		pkg: pkg,

		phpCheckStyleConfigPath: phpCheckStyleConfigPath,
		phpSourcesDir: phpSourcesDir,
		jsSourcesDir: jsSourcesDir,
		cssSourcesDir: cssSourcesDir,

		config: config,

		jshint: {
			files: [jsSourcesDir + '/**/*.js'],
			options: {
				globals: {
					console: true,
					module: true,
				},

				browser:true,
				latedef: 'nofunc',
				camelcase: true,
				eqeqeq: true,
				curly: true,
				immed: true,
				noarg: true,
				quotmark: 'single',
				undef: true,
				unused: 'vars',
				forin: true,
			},
		},

		copy: {
			dist: {
				files: [
					{ src: 'node_modules/jquery/dist/jquery.min.js', dest: 'public_html/lib/jquery.min.js' },
					{ src: 'node_modules/jquery.cookie/jquery.cookie.js', dest: 'public_html/lib/jquery.cookie.js' },
					{ src: 'node_modules/Mousetrap/mousetrap.min.js', dest: 'public_html/lib/mousetrap.min.js' },
					{ src: 'node_modules/pathjs/path.js', dest: 'public_html/lib/path.js' },
					{ src: 'node_modules/underscore/underscore-min.js', dest: 'public_html/lib/underscore.min.js' },
					{ src: 'node_modules/marked/lib/marked.js', dest: 'public_html/lib/marked.js' },
				]
			}
		},

		cssmin: {
			combine: {
				files: {
					'public_html/app.min.css': [cssSourcesDir + '/**/*.css'],
				},
			},
		},

		uglify: {
			dist: {
				options: {
					sourceMap: true,
				},
				files: {
					'public_html/app.min.js': [].concat(
						[jsSourcesDir + '/DI.js'],
						grunt.file.expand({
							filter: function(src) {
								return !src.match(/(DI|Bootstrap)\.js/);
							}
						}, jsSourcesDir + '/**/*.js'),
						[jsSourcesDir + '/Bootstrap.js']),
				},
			},
		},

		processhtml: {
			options: {
				data: {
					version: pkg.version + '@' + exec('git rev-parse --short HEAD', {silent: true}).output.trim(),
					customFaviconUrl: config.misc.customFaviconUrl,
					serviceName: config.basic.serviceName,
					templates: readTemplates(grunt),
					timestamp: grunt.template.today('isoDateTime'),
				}
			},
			dist: {
				files: {
					'public_html/app.min.html': ['public_html/index.html']
				}
			}
		},
	});

	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-processhtml');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-contrib-copy');

	grunt.registerTask('phpcheckstyle', 'Validate files with PHPCheckstyle.', function() {
		exec('php vendor/jbrooksuk/phpcheckstyle/run.php --config ' + phpCheckStyleConfigPath + ' --src ' + phpSourcesDir + ' --exclude di.php --format console');
	});

	grunt.registerTask('tests', 'Run all tests.', function() {
		exec('php vendor/phpunit/phpunit/phpunit -v --strict --bootstrap src/Bootstrap.php tests/');
	});

	grunt.registerTask('update', 'Upgrade database to newest version.', function() {
		exec('php scripts/upgrade.php');
	});
	grunt.registerTask('upgrade', ['update']);

	grunt.registerTask('optimizeComposer', 'Optimize Composer autoloader.', function() {
		exec('composer dumpautoload -o');
	});

	grunt.registerTask('build', ['clean', 'optimizeComposer', 'copy:dist', 'uglify', 'cssmin', 'processhtml']);

	grunt.registerTask('clean', 'Clean files produced with build task.', function() {
		fs.unlink('public_html/app.min.html');
		fs.unlink('public_html/app.min.js');
		fs.unlink('public_html/app.min.js.map');
		fs.unlink('public_html/app.min.css');
	});

	grunt.registerTask('checkstyle', ['jshint', 'phpcheckstyle']);
	grunt.registerTask('default', ['copy:dist', 'checkstyle', 'tests']);

};
