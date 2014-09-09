var path = require('path');

module.exports = function(grunt) {

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		phpCheckStyleConfigPath: path.join(path.resolve(), 'phpcheckstyle.cfg'),
		phpSourcesDir: path.join(path.resolve(), 'src'),
		jsSourcesDir: path.join(path.resolve(), 'public_html'),

		jshint: {
			files: ['<%= jsSourcesDir %>/**/*.js'],
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
				options: {
					execOptions: {
						cwd: path.join(path.resolve(), 'vendor/phpcheckstyle/phpcheckstyle'),
					},
				},
				command: 'php run.php --config <%= phpCheckStyleConfigPath %> --src <%= phpSourcesDir %> --exclude di.php --format console',
			},

			tests: {
				command: 'phpunit --strict --bootstrap src/AutoLoader.php tests/',
			},
		},
	});

	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-shell');
	grunt.registerTask('default', ['jshint', 'shell']);
	grunt.registerTask('tests', ['shell:tests']);

};
