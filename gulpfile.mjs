/**
 * Load plugins.
 */
import gulp from 'gulp';
import cached from 'gulp-cached';
import _sass from 'sass';
import gulpSass from 'gulp-sass';
import sourcemaps from 'gulp-sourcemaps';
import rename from 'gulp-rename';
import debug from 'gulp-debug';
import uglify from 'gulp-uglify';
import imagemin from 'gulp-imagemin';
import zip from 'gulp-zip';
import replace from 'gulp-replace';
import { createRequire } from 'module';
import { exec } from 'child_process';
import clean from 'gulp-clean';
import merge from 'merge-stream';

const sass = gulpSass( _sass );
const packageJSONRequire = createRequire( import.meta.url );
const packageJSON = packageJSONRequire( './package.json' );

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
		'!assets/**/*.css',
		'assets/vue/**/*.css',
		'assets/**/*.min.css',
		'assets/css/emails/*.css',
		'!vue-app/**',
		'!vue-app/',
		'!assets/wporg/**',
		'!assets/wporg',
		'!**/.github/**',
		'!**/.github',
		'!**/bin/**',
		'!**/bin',
		'!**/tests/**',
		'!.codeception',
		'!php-scoper/**',
		'!php-scoper',
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
		'!**/gulpfile.mjs',
		'!**/.eslintrc.js',
		'!**/.eslintignore.js',
		'!**/AUTHORS',
		'!**/Copying',
		'!**/Dockerfile',
		'!**/docker-compose.yml',
		'!**/Makefile',
		'!.packages/**',
		'!.packages/',
		'!vendor/composer/!(*.php)/**',
		'!vendor/wikimedia/**',
		'!vendor/wikimedia/',
		'!vendor/firebase/**',
		'!vendor/firebase/',
		// symfony is prefixed and located in vendor_prefixed folder.
		'!vendor/symfony/**',
		'!vendor/symfony/',
		// league is prefixed and located in vendor_prefixed folder.
		'!vendor/league/**',
		'!vendor/league/',
		// psr is prefixed and located in vendor_prefixed folder.
		'!vendor/psr/**',
		'!vendor/psr/',
		// We need only a specific service: Gmail. Others should be omitted.
		'!vendor_prefixed/google/apiclient-services/src/!(Gmail)/**',
		'!vendor_prefixed/google/apiclient-services/src/*.php',
		'vendor_prefixed/google/apiclient-services/src/Gmail.php',
		// We need only specific crypto-libraries. Others should be omitted.
		'!vendor_prefixed/phpseclib/phpseclib/phpseclib/Crypt/!(AES.php|Rijndael.php|RSA.php|Random.php)',
		'!vendor_prefixed/phpseclib/phpseclib/phpseclib/Net/**',
		'!vendor_prefixed/phpseclib/phpseclib/phpseclib/Net',
		'!vendor_prefixed/phpseclib/phpseclib/phpseclib/File/**',
		'!vendor_prefixed/phpseclib/phpseclib/phpseclib/File',
		'!vendor_prefixed/phpseclib/phpseclib/phpseclib/System/**',
		'!vendor_prefixed/phpseclib/phpseclib/phpseclib/System',
		// We don't need certain dev packages.
		'!vendor/dealerdirect/**',
		'!vendor/dealerdirect/',
		'!vendor/seld/**',
		'!vendor/seld/',
		'!vendor/squizlabs/**',
		'!vendor/squizlabs/',
		'!vendor/wp-coding-standards/**',
		'!vendor/wp-coding-standards/',
		'!vendor/wpforms/**',
		'!vendor/wpforms/',
		'!build.sh',
		'!phpcs.xml',
		'!crowdin.yml',
		'!.env.example',
		'!.env',
		'!.nvmrc',
	],
	lite_files: [
		'!assets/pro/**',
		'!src/Pro/**'
	],
	pro_files: [
		'loco.xml',
		'CHANGELOG.md',
		'!readme.txt',
		'!vendor/paragonie/random_compat/dist/**',
		'!vendor/paragonie/random_compat/dist/'
	],
	php: [
		'**/*.php',
		'!vendor/**',
		'!vendors/**',
		'!vendor_prefixed/**',
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
		"!gulpfile.mjs",
		"!assets/js/vendor/**",
		"!assets/pro/js/vendor/**",
		"!.codeception/**",
		"!.github/**",
		"!.packages/**",
		"!build/**",
		"!node_modules/**",
		"!php-scoper/**",
		"!vue-app/**",
		"!assets/vue/**",
		"!vendor/**",
		"!vendor_prefixed/**"
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
			.pipe( sass( { outputStyle: 'expanded' } ).on( 'error', sass.logError ) )
			.pipe( rename( function ( path ) {
				if ( /-pro-/.test( path.basename ) ) {
					path.dirname = '/assets/pro/css';
				}
				else {
					path.dirname = '/assets/css';
				}
				path.extname = '.css';
			} ) )
			.pipe( sourcemaps.write() )
			.pipe( gulp.dest( './' ) )
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
			.pipe( gulp.dest( './' ) )
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
	exec(
		'wp i18n make-pot ./ ./assets/languages/wp-mail-smtp.pot --slug="wp-mail-smtp" --domain="wp-mail-smtp" --package-name="WP Mail SMTP" --file-comment="" --exclude=".codeception,.github,.packages,build,node_modules,php-scoper,vendor,vendor-prefixed,assets/vue,vue-app"',
		function ( err, stdout, stderr ) {
			console.log( stdout );
			console.log( stderr );
			cb( err );
		}
	);
} );
gulp.task( 'pot:pro', function ( cb ) {
	exec(
		'wp i18n make-pot ./ ./assets/pro/languages/wp-mail-smtp-pro.pot --slug="wp-mail-smtp-pro" --domain="wp-mail-smtp-pro" --package-name="WP Mail SMTP" --file-comment="" --exclude=".codeception,.github,.packages,build,node_modules,php-scoper,vendor,vendor-prefixed,assets/vue,vue-app"',
		function ( err, stdout, stderr ) {
			console.log( stdout );
			console.log( stderr );
			cb( err );
		}
	);
} );
gulp.task( 'pot', gulp.series( 'pot:lite', 'pot:pro' ) );

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
gulp.task( 'zip', gulp.series( 'zip:lite', 'zip:pro' ) );

/**
 * Update composer with Lite and/or Pro dependencies.
 */
gulp.task( 'composer:lite', function ( cb ) {
	exec( 'composer build-lite', function ( err, stdout, stderr ) {
		console.log( stdout );
		console.log( stderr );
		cb( err );
	} );
} );
gulp.task( 'composer:pro', function ( cb ) {
	exec( 'composer build-pro', function ( err, stdout, stderr ) {
		console.log( stdout );
		console.log( stderr );
		cb( err );
	} );
} );
gulp.task( 'composer:delete_prefixed_vendor_libraries', function () {
	return gulp.src(
			[
				'vendor/aws',
				'vendor/google',
				'vendor/guzzlehttp',
				'vendor/league/oauth2-client',
				'vendor/mtdowling',
				'vendor/monolog',
				'vendor/paragonie/constant_time_encoding',
				'vendor/phpseclib',
				'vendor/psr/cache',
				'vendor/psr/http-message',
				'vendor/psr/http-client',
				'vendor/psr/http-factory',
				'vendor/psr/log',
				'vendor/sendinblue',
				'vendor/symfony/polyfill-mbstring',
				'vendor/symfony/polyfill-intl-idn',
				'vendor/symfony/deprecation-contracts',
				'vendor/mk-j',
			],
			{ allowEmpty: true, read: false }
		)
		.pipe( clean() );
} );
gulp.task( 'composer:delete_unneeded_vendor_libraries', function () {
	return gulp.src(
		[
			'vendor/firebase',
			'vendor/wikimedia',
		],
		{ allowEmpty: true, read: false }
	)
		.pipe( clean() );
} );
gulp.task( 'composer:create_vendor_prefixed_folder', function () {
	return gulp.src( '*.*', { read: false } )
		.pipe( gulp.dest( './vendor_prefixed' ) );
} );
gulp.task( 'composer:prefix_lite', function ( cb ) {
	exec( 'composer prefix-dependencies-lite', function ( err, stdout, stderr ) {
		console.log( stdout );
		console.log( stderr );
		cb( err );
	} );
} );
gulp.task( 'composer:prefix', function ( cb ) {
	exec( 'composer prefix-dependencies-optimize', function ( err, stdout, stderr ) {
		console.log( stdout );
		console.log( stderr );
		cb( err );
	} );
} );
/**
 * Remove the pro autoload files for the lite build
 */
gulp.task( 'composer:remove_pro_autoload_files', function () {
	return gulp.src( [ 'composer.json' ] )
		.pipe(
			replace(
				/"vendor_prefixed\/mtdowling\/jmespath.php\/src\/JmesPath.php",/gm,
				''
			)
		)
		.pipe(
			replace(
				/"vendor_prefixed\/aws\/aws-sdk-php\/src\/functions.php",/gm,
				''
			)
		)
		.pipe( gulp.dest( './' ) );
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
	var from = /Plugin Name: WP Mail SMTP$/gm;
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
 * Update namespace of certain files that php-scoper can't patch.
 */
gulp.task( 'prefix_outside_files', function () {
	return merge(
		gulp.src( [ 'vendor/codeception/codeception/src/Codeception/Util/Uri.php' ], { allowEmpty: true } )
			.pipe( replace( /use GuzzleHttp\\Psr7\\Uri as Psr7Uri;/gm, 'use WPMailSMTP\\Vendor\\GuzzleHttp\\Psr7\\Uri as Psr7Uri;' ) )
			.pipe( gulp.dest( 'vendor/codeception/codeception/src/Codeception/Util/' ) ),

		gulp.src( [ 'vendor_prefixed/symfony/polyfill-mbstring/bootstrap.php', 'vendor_prefixed/symfony/polyfill-mbstring/bootstrap80.php' ], { allowEmpty: true } )
			.pipe( replace( /use Symfony\\Polyfill\\Mbstring/gm, 'use WPMailSMTP\\Vendor\\Symfony\\Polyfill\\Mbstring' ) )
			.pipe( gulp.dest( 'vendor_prefixed/symfony/polyfill-mbstring/' ) ),

		gulp.src( [ 'vendor_prefixed/symfony/polyfill-mbstring/Resources/mb_convert_variables.php8' ], { allowEmpty: true } )
			.pipe( replace( /use Symfony\\Polyfill\\Mbstring/gm, 'use WPMailSMTP\\Vendor\\Symfony\\Polyfill\\Mbstring' ) )
			.pipe( gulp.dest( 'vendor_prefixed/symfony/polyfill-mbstring/Resources/' ) ),

		gulp.src( [ 'vendor_prefixed/symfony/polyfill-intl-idn/bootstrap.php', 'vendor_prefixed/symfony/polyfill-intl-idn/bootstrap80.php' ], { allowEmpty: true } )
			.pipe( replace( /use Symfony\\Polyfill\\Intl\\Idn/gm, 'use WPMailSMTP\\Vendor\\Symfony\\Polyfill\\Intl\\Idn' ) )
			.pipe( gulp.dest( 'vendor_prefixed/symfony/polyfill-intl-idn/' ) ),
	);
} );

/**
 * PHP version check, if at least PHP 7.3 is in use.
 */
gulp.task( 'php:check-build-version', function ( cb ) {
	exec(
		'composer check-build-php-version',
		function ( err, stdout, stderr ) {
			console.log( stdout );
			console.log( stderr );
			cb( err );
		}
	);
} );

/**
 * Vue app build tasks.
 */
gulp.task( 'vue:install', function ( cb ) {
	exec(
		'cd vue-app && npm install',
		function ( err, stdout, stderr ) {
			console.log( stdout );
			console.log( stderr );
			cb( err );
		}
	);
} );
gulp.task( 'vue:build', function ( cb ) {
	exec(
		'cd vue-app && npm run build-app',
		function ( err, stdout, stderr ) {
			console.log( stdout );
			console.log( stderr );
			cb( err );
		}
	);
} );
gulp.task( 'vue:translations', function ( cb ) {
	exec(
		'cd vue-app && npx pot-to-php languages/wp-mail-smtp-vue.pot ../assets/languages/wp-mail-smtp-vue.php wp-mail-smtp',
		function ( err, stdout, stderr ) {
			console.log( stdout );
			console.log( stderr );
			cb( err );
		}
	);
} );
gulp.task( 'vue', gulp.series( 'vue:install', 'vue:build', 'vue:translations' ) );

/**
 * Task: build.
 */
gulp.task( 'build:assets', gulp.series( gulp.parallel( 'css', 'js', 'img', 'vue' ), 'replace_ver', 'pot' ) );
gulp.task( 'build:lite', gulp.series( gulp.parallel( 'css', 'js', 'img', 'vue' ), 'replace_ver', 'pot:lite', 'rename:lite', 'composer:lite', 'zip:lite' ) );
gulp.task( 'build:pro', gulp.series( gulp.parallel( 'css', 'js', 'img', 'vue' ), 'replace_ver', 'pot', 'rename:pro', 'composer:pro', 'zip:pro' ) );
gulp.task( 'build:test', gulp.series( 'rename:lite', 'composer:lite', 'zip:lite', 'rename:pro', 'composer:pro', 'zip:pro' ) );
gulp.task( 'build', gulp.series( gulp.parallel( 'css', 'js', 'img', 'vue' ), 'replace_ver', 'pot', 'rename:lite', 'composer:lite', 'zip:lite', 'rename:pro', 'composer:pro', 'zip:pro' ) );

// Build tasks without PHP composer install step
// The composer install should be done on PHP 5.6 before running below commands:
// `composer build-lite-step-1` or `composer build-pro-step-1`.
gulp.task( 'build:lite_no_composer', gulp.series( 'php:check-build-version', gulp.parallel( 'css', 'js', 'img', 'vue' ), 'replace_ver', 'rename:lite', 'pot:lite', 'composer:prefix_lite', 'zip:lite' ) );
gulp.task( 'build:pro_no_composer', gulp.series( 'php:check-build-version', 'rename:pro', 'build:assets', 'composer:prefix', 'zip:pro' ) );

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
