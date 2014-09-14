var path = require('path');
var fs = require('fs');
var ini = require('ini');

module.exports = function(grunt) {

	var phpCheckStyleConfigPath = path.join(path.resolve(), 'phpcheckstyle.cfg');
	var phpSourcesDir = path.join(path.resolve(), 'src');
	var jsSourcesDir = path.join(path.resolve(), 'public_html/js');
	var cssSourcesDir = path.join(path.resolve(), 'public_html/css');
	var templatesDir = path.join(path.resolve(), 'public_html/templates');

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

	function readTemplates() {
		var templatePaths = grunt.file.expand(templatesDir + '/**/*.tpl');
		var templates = {};
		for (var i = 0; i < templatePaths.length; i ++) {
			var templatePath = templatePaths[i];
			templates[path.basename(templatePath)] = fs.readFileSync(templatePath);
		}
		return templates;
	}

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

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

		shell: {
			phpcheckstyle: {
				command: 'php vendor/jbrooksuk/phpcheckstyle/run.php --config <%= phpCheckStyleConfigPath %> --src <%= phpSourcesDir %> --exclude di.php --format console',
			},

			tests: {
				command: 'phpunit --strict --bootstrap src/AutoLoader.php tests/',
			},
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
					serviceName: config.basic.serviceName,
					templates: readTemplates(),
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
	grunt.loadNpmTasks('grunt-shell');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-cssmin');

	grunt.registerTask('default', ['checkstyle', 'tests']);
	grunt.registerTask('checkstyle', ['jshint', 'shell:phpcheckstyle']);
	grunt.registerTask('tests', ['shell:tests']);

	grunt.registerTask('clean', function() {
		fs.unlink('public_html/app.min.html');
		fs.unlink('public_html/app.min.js');
		fs.unlink('public_html/app.min.js.map');
		fs.unlink('public_html/app.min.css');
	});
	grunt.registerTask('build', ['clean', 'uglify', 'cssmin', 'processhtml']);

};
