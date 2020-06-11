/**
 * Load plugins.
 */
var gulp = require( 'gulp' ),
	cached = require( 'gulp-cached' ),
	sass = require( 'gulp-sass' ),
	sourcemaps = require( 'gulp-sourcemaps' ),
	rename = require( 'gulp-rename' ),
	debug = require( 'gulp-debug' ),
	uglify = require( 'gulp-uglify' ),
	imagemin = require( 'gulp-imagemin' ),
	zip = require( 'gulp-zip' ),
	replace = require( 'gulp-replace' ),
	packageJSON = require( './package.json' ),
	exec = require( 'child_process' ).exec;

var plugin = {
	name: 'WP Mail SMTP',
	slug: 'wp-mail-smtp',
	files: [
		'**',
		// Exclude all the files/dirs below. Note the double negate (when ! is used inside the exclusion) - we may actually need some things.
		'!src/**/_*.php',
		'!**/*.map',
		'!LICENSE',
		'!assets/**/*.scss',
		'!assets/wporg/**',
		'!assets/wporg',
		'!**/.github/**',
		'!**/.github',
		'!**/bin/**',
		'!**/bin',
		'!**/tests/**',
		'!**/tests',
		'!**/Tests/**',
		'!**/Tests',
		'!**/test/**',
		'!**/test',
		'!**/Test/**',
		'!**/Test',
		'!**/build/**',
		'!**/build',
		'!**/example/**',
		'!**/example',
		'!**/examples/**',
		'!**/examples',
		'!**/doc/**',
		'!**/doc',
		'!**/docs/**',
		'!**/docs',
		'!**/node_modules/**',
		'!**/node_modules',
		'!**/*.md',
		'!**/*.sh',
		'!**/*.rst',
		'!**/*.xml',
		'!**/*.yml',
		'!**/*.dist',
		'!**/*.json',
		'!**/*.lock',
		'!**/gulpfile.js',
		'!**/.eslintrc.js',
		'!**/.eslintignore.js',
		'!**/AUTHORS',
		'!**/Copying',
		'!**/Dockerfile',
		'!**/Makefile',
		'!.packages/**',
		'!.packages/',
		'!vendor/composer/!(*.php)/**',
		'!vendor/firebase/**',
		'!vendor/firebase/',
		// We need only a specific service: Gmail. Others should be omitted.
		'!vendor/google/apiclient-services/src/Google/Service/!(Gmail)/**',
		'!vendor/google/apiclient-services/src/Google/Service/*.php',
		'vendor/google/apiclient-services/src/Google/Service/Gmail.php',
		// We need only specific crypto-libraries. Others should be omitted.
		'!vendor/phpseclib/phpseclib/phpseclib/Crypt/!(AES.php|Rijndael.php|RSA.php|Random.php)',
		'!vendor/phpseclib/phpseclib/phpseclib/Net/**',
		'!vendor/phpseclib/phpseclib/phpseclib/Net',
		'!vendor/phpseclib/phpseclib/phpseclib/File/**',
		'!vendor/phpseclib/phpseclib/phpseclib/File',
		'!vendor/phpseclib/phpseclib/phpseclib/System/**',
		'!vendor/phpseclib/phpseclib/phpseclib/System',
		// We don't need certain dev packages.
		'!vendor/dealerdirect/**',
		'!vendor/dealerdirect/',
		'!vendor/seld/**',
		'!vendor/seld/',
		'!vendor/squizlabs/**',
		'!vendor/squizlabs/',
		'!vendor/wikimedia/**',
		'!vendor/wikimedia/',
		'!vendor/wp-coding-standards/**',
		'!vendor/wp-coding-standards/',
		'!vendor/wpforms/**',
		'!vendor/wpforms/',
	],
	lite_files: [
		'!assets/pro/**',
		'!src/Pro/**'
	],
	pro_files: [
		'loco.xml',
		'!readme.txt'
	],
	php: [
		'**/*.php',
		'!vendor/**',
		'!vendors/**',
		"!vendor_prefixed/**",
		'!tests/**'
	],
	scss: [
		'assets/css/**/*.scss',
		'assets/pro/css/**/*.scss'
	],
	js: [
		'assets/js/*.js',
		'assets/pro/js/*.js',
		'!assets/js/*.min.js',
		'!assets/pro/js/*.min.js'
	],
	images: [
		'assets/images/**/*',
		'assets/pro/images/**/*',
		'assets/wporg/**/*'
	],
	files_replace_ver: [
		"**/*.php",
		"**/*.js",
		"!**/*.min.js",
		"!gulpfile.js",
		"!assets/js/vendor/**",
		"!assets/pro/js/vendor/**",
		"!build/**",
		"!vendor/**",
		"!vendors/**",
		"!vendor_prefixed/**",
		"!node_modules/**"
	]
};

/**
 * Compile SCSS to CSS, compress.
 */
gulp.task( 'css', function () {
	return gulp.src( plugin.scss )
			   // UnMinified file.
			   .pipe( cached( 'processCSS' ) )
			   .pipe( sourcemaps.init() )
			   // Minified file.
			   .pipe( sass( { outputStyle: 'compressed' } ).on( 'error', sass.logError ) )
			   .pipe( rename( function ( path ) {
				   if ( /-pro-/.test( path.basename ) ) {
					   path.dirname = '/assets/pro/css';
				   }
				   else {
					   path.dirname = '/assets/css';
				   }
				   path.extname = '.min.css';
			   } ) )
			   .pipe( sourcemaps.write( '.' ) )
			   .pipe( gulp.dest( '.' ) )
			   .pipe( debug( { title: '[css]' } ) );
} );

/**
 * Compress js.
 */
gulp.task( 'js', function () {
	return gulp.src( plugin.js )
			   .pipe( cached( 'processJS' ) )
			   .pipe( uglify() ).on( 'error', console.log )
			   .pipe( rename( function ( path ) {
				   if ( /-pro-/.test( path.basename ) ) {
					   path.dirname = '/assets/pro/js';
				   }
				   else {
					   path.dirname = '/assets/js';
				   }
				   path.basename += '.min';
			   } ) )
			   .pipe( gulp.dest( '.' ) )
			   .pipe( debug( { title: '[js]' } ) );
} );

/**
 * Optimize image files.
 */
gulp.task( 'img', function () {
	return gulp.src( plugin.images )
			   .pipe( imagemin() )
			   .pipe( gulp.dest( function ( file ) {
				   return file.base;
			   } ) )
			   .pipe( debug( { title: '[img]' } ) );
} );

/**
 * Generate .pot files for Lite and Pro.
 */
gulp.task( 'pot:lite', function ( cb ) {
	exec( 'wp i18n make-pot ./ ./assets/languages/wp-mail-smtp.pot --slug="wp-mail-smtp" --domain="wp-mail-smtp" --package-name="WP Mail SMTP" --file-comment=""', function ( err, stdout, stderr ) {
		console.log( stdout );
		console.log( stderr );
		cb( err );
	} );
} );
gulp.task( 'pot:pro', function ( cb ) {
	exec( 'wp i18n make-pot ./ ./assets/pro/languages/wp-mail-smtp-pro.pot --slug="wp-mail-smtp-pro" --domain="wp-mail-smtp-pro" --package-name="WP Mail SMTP" --file-comment=""', function ( err, stdout, stderr ) {
		console.log( stdout );
		console.log( stderr );
		cb( err );
	} );
} );
gulp.task( 'pot', gulp.parallel( 'pot:lite', 'pot:pro' ) );

/**
 * Generate a .zip file.
 */
gulp.task( 'zip:lite', function () {
	var files = plugin.files.concat( plugin.lite_files );

	// Modifying 'base' to include plugin directory in a zip.
	return gulp.src( files, { base: '.' } )
		.pipe( rename( function ( file ) {
			file.dirname = plugin.slug + '/' + file.dirname;
		} ) )
		.pipe( zip( plugin.slug + '-' + packageJSON.version + '.zip' ) )
		.pipe( gulp.dest( './build' ) )
		.pipe( debug( { title: '[zip]' } ) );
} );
gulp.task( 'zip:pro', function () {
	var files = plugin.files.concat( plugin.pro_files );

	// Modifying 'base' to include plugin directory in a zip.
	return gulp.src( files, { base: '.' } )
		.pipe( rename( function ( file ) {
			file.dirname = plugin.slug + '-pro/' + file.dirname;
		} ) )
		.pipe( zip( plugin.slug + '-pro-' + packageJSON.version + '.zip' ) )
		.pipe( gulp.dest( './build' ) )
		.pipe( debug( { title: '[zip]' } ) );
} );

/**
 * Update composer with Lite and/or Pro dependencies.
 */
gulp.task( 'composer:lite', function ( cb ) {
	exec( 'composer update --root-reqs --no-dev --no-suggest --no-plugins', function ( err, stdout, stderr ) {
		console.log( stdout );
		console.log( stderr );
		cb( err );
	} );
} );
gulp.task( 'composer:pro', function ( cb ) {
	exec( 'composer update --root-reqs --no-suggest', function ( err, stdout, stderr ) {
		console.log( stdout );
		console.log( stderr );
		cb( err );
	} );
} );

/**
 * Rename plugin name defined the main plugin file.
 */
gulp.task( 'rename:lite', function () {
	var from = /Plugin Name: WP Mail SMTP Pro/gm;
	var to   = 'Plugin Name: WP Mail SMTP';

	return gulp.src( [ 'wp_mail_smtp.php' ] )
			   .pipe( replace( from, to ) )
			   .pipe( gulp.dest( './' ) );
} );
gulp.task( 'rename:pro', function () {
	var from = /Plugin Name: WP Mail SMTP/gm;
	var to   = 'Plugin Name: WP Mail SMTP Pro';

	return gulp.src( [ 'wp_mail_smtp.php' ] )
			   .pipe( replace( from, to ) )
			   .pipe( gulp.dest( './' ) );
} );

/**
 * Replace plugin version with one from package.json in the main plugin file.
 */
gulp.task( 'replace_plugin_file_ver', function () {
	return gulp.src( [ 'wp_mail_smtp.php' ] )
		.pipe(
			// File header.
			replace(
				/Version: ((\*)|([0-9]+(\.((\*)|([0-9]+(\.((\*)|([0-9]+)))?)))?))/gm,
				'Version: ' + packageJSON.version
			)
		)
		.pipe(
			// PHP constant.
			replace(
				/define\( 'WPMS_PLUGIN_VER', '((\*)|([0-9]+(\.((\*)|([0-9]+(\.((\*)|([0-9]+)))?)))?))' \);/gm,
				'define( \'WPMS_PLUGIN_VER\', \'' + packageJSON.version + '\' );'
			)
		)
		.pipe( gulp.dest( './' ) );
} );
/**
 * Replace plugin version with one from package.json in @since comments in plugin PHP and JS files.
 */
gulp.task( 'replace_since_ver', function () {
	return gulp.src( plugin.files_replace_ver )
		.pipe(
			replace(
				/@since {VERSION}/g,
				'@since ' + packageJSON.version
			)
		)
		.pipe( gulp.dest( './' ) );
} );
gulp.task( 'replace_ver', gulp.series( 'replace_plugin_file_ver', 'replace_since_ver' ) );

/**
 * Task: build.
 */
gulp.task( 'build:lite', gulp.series( gulp.parallel( 'css', 'js', 'img', 'pot:lite' ), 'replace_ver', 'rename:lite', 'composer:lite', 'zip:lite' ) );
gulp.task( 'build:pro', gulp.series( gulp.parallel( 'css', 'js', 'img', 'pot' ), 'replace_ver', 'rename:pro', 'composer:pro', 'zip:pro' ) );
gulp.task( 'build:test', gulp.series( 'rename:lite', 'composer:lite', 'zip:lite', 'rename:pro', 'composer:pro', 'zip:pro' ) );
gulp.task( 'build', gulp.series( gulp.parallel( 'css', 'js', 'img', 'pot' ), 'replace_ver', 'rename:lite', 'composer:lite', 'zip:lite', 'rename:pro', 'composer:pro', 'zip:pro' ) );

/**
 * Look out for relevant sass/js changes.
 */
gulp.task( 'watch', function () {
	gulp.watch( plugin.scss, gulp.parallel( 'css' ) );
	gulp.watch( plugin.js, gulp.parallel( 'js' ) );
} );

/**
 * Default.
 */
gulp.task( 'default', gulp.parallel( 'css', 'js' ) );
