/**
 * Load plugins.
 */
var gulp        = require('gulp'),
	cached      = require('gulp-cached'),
	gutil       = require('gulp-util'),
	watch       = require('gulp-watch'),
	sass        = require('gulp-sass'),
	sourcemaps  = require('gulp-sourcemaps'),
	rename      = require('gulp-rename'),
	debug       = require('gulp-debug'),
	uglify      = require('gulp-uglify'),
	imagemin    = require('gulp-imagemin'),
	wpPot       = require('gulp-wp-pot'),
	zip         = require('gulp-zip'),
	runSequence = require('run-sequence');

var plugin = {
	name: 'WP Mail SMTP',
	slug: 'wp-mail-smtp',
	files: [
		'**',
		'!**/*.map',
		'!assets/scss/**',
		'!assets/scss',
		'!assets/wporg/**',
		'!assets/wporg',
		'!**/.github/**',
		'!**/.github',
		'!**/bin/**',
		'!**/bin',
		'!**/tests/**',
		'!**/tests',
		'!**/Test/**',
		'!**/Test',
		'!**/Tests/**',
		'!**/Tests',
		'!**/build/**',
		'!**/build',
		'!**/examples/**',
		'!**/examples',
		'!**/doc/**',
		'!**/doc',
		'!**/docs/**',
		'!**/docs',
		'!**/node_modules/**',
		'!**/node_modules',
		'!**/*.md',
		'!**/*.rst',
		'!**/*.xml',
		'!**/*.dist',
		'!**/*.json',
		'!**/*.lock',
		'!**/gulpfile.js',
		'!LICENSE', // but include licenses in the packages
		'!**/Makefile',
		'!**/AUTHORS',
		'!vendor/composer/installers/**',
		'!vendor/composer/installers',
		'!vendor/composer/installed.json',
		'!vendor/firebase/**',
		'!vendor/firebase',
		// We need only a specific service: Gmail. Others should be omitted.
		'!vendor/google/apiclient-services/src/Google/Service/!(Gmail)/**',
		'!vendor/google/apiclient-services/src/Google/Service/!(Gmail|Gmail.php)'
	],
	php: [
		'**/*.php',
		'!vendor/**',
		'!tests/**'
	],
	sass: [
		'assets/scss/**/*.scss'
	],
	js: [
		'assets/js/*.js',
		'!assets/js/*.min.js'
	],
	images: [
		'assets/images/**/*',
		'assets/wporg/**/*'
	]
};

/**
 * Task: process-sass.
 *
 * Compile, compress.
 */
gulp.task('process-sass', function() {
	gutil.log(
		gutil.colors.gray('====== ') +
		gutil.colors.white.bold('Processing .scss files') +
		gutil.colors.gray(' ======')
	);

	return gulp.src(plugin.sass)
		// UnMinified file.
		.pipe(cached('processSASS'))
		.pipe(sourcemaps.init())
		// Minified file.
		.pipe(sass({outputStyle: 'compressed'}).on('error',sass.logError))
		.pipe(rename(function(path){
			path.dirname = '/assets/css';
			path.extname = '.min.css';
		}))
	    .pipe(sourcemaps.write('.'))
		.pipe(gulp.dest('.'))
		.pipe(debug({title: '[sass]'}));
});

/**
 * Task: process-js.
 *
 * Compress js.
 */
gulp.task('process-js', function() {
	gutil.log(
		gutil.colors.gray('====== ') +
		gutil.colors.white.bold('Processing .js files') +
		gutil.colors.gray(' ======')
	);

	return gulp.src(plugin.js)
		.pipe(cached('processJS'))
		.pipe(uglify()).on('error', gutil.log)
		.pipe(rename(function(path){
			path.dirname += '/assets/js';
			path.basename += '.min';
		}))
		.pipe(gulp.dest('.'))
		.pipe(debug({title: '[js]'}));
});

/**
 * Task: process-img.
 *
 * Optimize image files.
 */
gulp.task('process-img', function () {
	gutil.log(
		gutil.colors.gray('====== ') +
		gutil.colors.white.bold('Processing image files') +
		gutil.colors.gray(' ======')
	);

	return gulp.src(plugin.images)
		.pipe(cached('processIMG'))
		.pipe(imagemin())
		.pipe(gulp.dest(function (file) {
			return file.base;
		}))
		.pipe(debug({title: '[img]'}));
});

/**
 * Task: process-pot.
 *
 * Generate a .pot file.
 */
gulp.task('process-pot', function() {
	gutil.log(
		gutil.colors.gray('====== ') +
		gutil.colors.white.bold('Generating a .pot file') +
		gutil.colors.gray(' ======')
	);

	return gulp.src(plugin.php)
		.pipe(wpPot( {
			domain: plugin.slug,
			package: plugin.name,
			team: 'WPForms <support@wpforms.com>'
		} ))
		.pipe(gulp.dest('languages/'+plugin.slug+'.pot'))
		.pipe(debug({title: '[pot]'}));
});

/**
 * Task: process-pot.
 *
 * Generate a .zip file.
 */
gulp.task('process-zip', function() {
	gutil.log(
		gutil.colors.gray('====== ') +
		gutil.colors.white.bold('Generating a .zip file') +
		gutil.colors.gray(' ======')
	);

	// Modifying 'base' to include plugin directory in a zip.
	return gulp.src(plugin.files, {base: '../'})
		.pipe(zip(plugin.slug + '.zip'))
		.pipe(gulp.dest('./build'))
		.pipe(debug({title: '[zip]'}));
});

/**
 * Task: build.
 *
 * Build a plugin by processing all required files.
 */
gulp.task('build', function() {
	runSequence('process-sass', 'process-js', 'process-img', 'process-pot', 'process-zip');
});

/**
 * Task: watch.
 *
 * Look out for relevant sass/js changes.
 */
gulp.task('watch', function() {
	gulp.watch(plugin.sass, ['process-sass']);
	gulp.watch(plugin.js, ['process-js']);
});

/**
 * Default.
 */
gulp.task('default', function(callback) {
	runSequence('process-sass','process-js', callback);
});
