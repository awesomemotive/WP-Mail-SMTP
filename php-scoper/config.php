<?php

use Isolated\Symfony\Component\Finder\Finder;

/**
 * Get the list of files in the provided path.
 *
 * @param string $path The relative path to the folder to get the file list for.
 *
 * @return array
 */
function wms_php_scoper_get_list_of_files( $path ) {

	$files = [];

	$directory = new RecursiveDirectoryIterator( __DIR__ . '/' . $path );
	$iterator  = new RecursiveIteratorIterator( $directory );

	while ( $iterator->valid() ) {

		if ( $iterator->isDot() || $iterator->isDir() ) {
			$iterator->next();
			continue;
		}

		$files[] = $iterator->getPathname();

		$iterator->next();
	}

	return $files;
}

$config = [
	'prefix'                     => 'WPMailSMTP\Vendor',
	'whitelist-global-constants' => false,
	'whitelist-global-classes'   => false,
	'whitelist-global-functions' => false,

	/*
	By default when running php-scoper add-prefix, it will prefix all relevant code found in the current working
	directory. You can however define which files should be scoped by defining a collection of Finders in the
	following configuration key.
	For more see: https://github.com/humbug/php-scoper#finders-and-paths.
	*/
	'finders'                    => [
		Finder::create()
			->files()
			->in( 'vendor/google' )
			->exclude(
				[
					'apiclient-services',
				]
			)
			->name( [ '*.php', 'LICENSE', 'composer.json' ] ),
		Finder::create()
			->files()
			->in( 'vendor/google/apiclient-services/src/Gmail/' ),
		Finder::create()
			->files()
			->in( 'vendor/google/apiclient-services/src/' )
			->name( 'Gmail.php' ),
		Finder::create()
			->files()
			->in( 'vendor/guzzlehttp' )
			->name( [ '*.php', 'LICENSE', 'composer.json' ] ),
		Finder::create()
			->files()
			->in( 'vendor/monolog' )
			->name( [ '*.php', 'LICENSE', 'composer.json' ] ),
		Finder::create()
			->files()
			->in( 'vendor/paragonie/constant_time_encoding' )
			->exclude(
				[
					'tests',
				]
			)
			->name( [ '*.php', 'LICENSE.txt', 'composer.json' ] ),
		Finder::create()
			->files()
			->in(
				[
					'vendor/psr/cache',
					'vendor/psr/http-message',
					'vendor/psr/http-client',
					'vendor/psr/http-factory',
					'vendor/psr/log',
				]
			)
			->name( [ '*.php', 'LICENSE', 'composer.json' ] ),
		Finder::create()
			->files()
			->in( 'vendor/symfony/polyfill-mbstring' )
			->name( [ '*.php', '*.php8', 'LICENSE', 'composer.json' ] ),
		Finder::create()
			->files()
			->in( 'vendor/symfony/polyfill-php72' )
			->name( [ '*.php', 'LICENSE', 'composer.json' ] ),
		Finder::create()
			->files()
			->in( 'vendor/symfony/polyfill-intl-idn' )
			->name( [ '*.php', 'LICENSE', 'composer.json' ] ),
		Finder::create()
			->files()
			->in( 'vendor/symfony/deprecation-contracts' )
			->name( [ '*.php', 'LICENSE', 'composer.json' ] ),
	],

	/*
	When scoping PHP files, there will be scenarios where some of the code being scoped indirectly references the
	original namespace. These will include, for example, strings or string manipulations. PHP-Scoper has limited
	support for prefixing such strings. To circumvent that, you can define patchers to manipulate the file to your
	heart contents.
	For more see: https://github.com/humbug/php-scoper#patchers.
	*/
	'patchers'                   => [
		/**
		 * Prefix the dynamic alias class generation in Google's apiclient lib.
		 *
		 * @param string $filePath The path of the current file.
		 * @param string $prefix   The prefix to be used.
		 * @param string $content  The content of the specific file.
		 *
		 * @return string The modified content.
		 */
		function ( $file_path, $prefix, $content ) {
			if ( strpos( $file_path, 'google/apiclient/src/aliases.php' ) !== false ) {
				return str_replace(
					'class_alias($class, $alias);',
					sprintf( 'class_alias($class, \'%s\\\\\' . $alias);', addslashes( $prefix ) ),
					$content
				);
			}
			return $content;
		},

		/**
		 * Prefix the Guzzle client interface version checks in Google HTTP Handler Factory and
		 * Google Credentials Loader.
		 *
		 * @param string $filePath The path of the current file.
		 * @param string $prefix   The prefix to be used.
		 * @param string $content  The content of the specific file.
		 *
		 * @return string The modified content.
		 */
		function ( $file_path, $prefix, $content ) {
			if (
				strpos( $file_path, 'google/auth/src/HttpHandler/HttpHandlerFactory.php' ) !== false ||
				strpos( $file_path, 'google/auth/src/CredentialsLoader.php' ) !== false ||
				strpos( $file_path, 'google/apiclient/src/Client.php' ) !== false ||
				strpos( $file_path, 'google/apiclient/src/AuthHandler/AuthHandlerFactory.php' ) !== false ||
				strpos( $file_path, 'aws/aws-sdk-php/src/functions.php' ) !== false
			) {
				return str_replace(
					'GuzzleHttp\\\\ClientInterface',
					sprintf( '%s\\\\GuzzleHttp\\\\ClientInterface', addslashes( $prefix ) ),
					$content
				);
			}
			return $content;
		},

		/**
		 * Prefix the dynamic class generation in League's oauth2-client lib.
		 *
		 * @param string $filePath The path of the current file.
		 * @param string $prefix   The prefix to be used.
		 * @param string $content  The content of the specific file.
		 *
		 * @return string The modified content.
		 */
		function( $file_path, $prefix, $content ) {
			if ( strpos( $file_path, 'league/oauth2-client/src/Grant/GrantFactory.php' ) !== false ) {
				return str_replace(
					'$class = \'League\\\\OAuth2\\\\Client\\\\Grant\\\\\' . $class;',
					sprintf( '$class = \'%s\\\\League\\\\OAuth2\\\\Client\\\\Grant\\\\\' . $class;', addslashes( $prefix ) ),
					$content
				);
			}

			return $content;
		},

		/**
		 * Prefix the Monolog namespace in strings.
		 *
		 * @param string $filePath The path of the current file.
		 * @param string $prefix   The prefix to be used.
		 * @param string $content  The content of the specific file.
		 *
		 * @return string The modified content.
		 */
		function( $file_path, $prefix, $content ) {
			if (
				strpos( $file_path, 'monolog/monolog/src/Monolog/Handler/PHPConsoleHandler.php' ) !== false ||
				strpos( $file_path, 'monolog/monolog/src/Monolog/Processor/IntrospectionProcessor.php' ) !== false ||
				strpos( $file_path, 'monolog/monolog/src/Monolog/Handler/BrowserConsoleHandler.php' ) !== false ||
				strpos( $file_path, 'monolog/monolog/src/Monolog/Handler/FilterHandler.php' ) !== false ||
				strpos( $file_path, 'monolog/monolog/src/Monolog/Handler/FingersCrossed/ChannelLevelActivationStrategy.php' ) !== false ||
				strpos( $file_path, 'monolog/monolog/src/Monolog/Utils.php' ) !== false ||
				strpos( $file_path, 'monolog/monolog/src/Monolog/Handler/TestHandler.php' ) !== false
			) {
				return str_replace(
					'Monolog\\\\',
					sprintf( '%s\\\\Monolog\\\\', addslashes( $prefix ) ),
					$content
				);
			}

			return $content;
		},

		/**
		 * Prefix the aws-sdk-php namespace in strings for AwsClient class.
		 *
		 * @param string $filePath The path of the current file.
		 * @param string $prefix   The prefix to be used.
		 * @param string $content  The content of the specific file.
		 *
		 * @return string The modified content.
		 */
		function( $file_path, $prefix, $content ) {
			if ( strpos( $file_path, 'aws/aws-sdk-php/src/AwsClient.php' ) !== false ) {
				return str_replace(
					'Aws\\\\{$service}\\\\Exception\\\\{$service}Exception',
					sprintf( '%s\\\\Aws\\\\{$service}\\\\Exception\\\\{$service}Exception', addslashes( $prefix ) ),
					$content
				);
			}

			return $content;
		},

		/**
		 * Prefix the aws-sdk-php namespace in strings for the MultiRegionClient and Sdk classes.
		 *
		 * @param string $filePath The path of the current file.
		 * @param string $prefix   The prefix to be used.
		 * @param string $content  The content of the specific file.
		 *
		 * @return string The modified content.
		 */
		function( $file_path, $prefix, $content ) {
			if (
				strpos( $file_path, 'aws/aws-sdk-php/src/MultiRegionClient.php' ) !== false ||
				strpos( $file_path, 'aws/aws-sdk-php/src/Sdk.php' ) !== false
			) {
				return str_replace(
					'Aws\\\\{$namespace}\\\\{$namespace}Client',
					sprintf( '%s\\\\Aws\\\\{$namespace}\\\\{$namespace}Client', addslashes( $prefix ) ),
					$content
				);
			}

			return $content;
		},

		/**
		 * Prefix the aws-sdk-php namespace in strings for the Sdk class.
		 *
		 * @param string $filePath The path of the current file.
		 * @param string $prefix   The prefix to be used.
		 * @param string $content  The content of the specific file.
		 *
		 * @return string The modified content.
		 */
		function( $file_path, $prefix, $content ) {
			if ( strpos( $file_path, 'aws/aws-sdk-php/src/Sdk.php' ) !== false ) {
				return str_replace(
					'Aws\\\\MultiRegionClient',
					sprintf( '%s\\\\Aws\\\\MultiRegionClient', addslashes( $prefix ) ),
					$content
				);
			}

			return $content;
		},

		/**
		 * Prefix the aws-sdk-php namespace in strings for the Sdk class.
		 *
		 * @param string $filePath The path of the current file.
		 * @param string $prefix   The prefix to be used.
		 * @param string $content  The content of the specific file.
		 *
		 * @return string The modified content.
		 */
		function( $file_path, $prefix, $content ) {
			if ( strpos( $file_path, 'aws/aws-sdk-php/src/Sdk.php' ) !== false ) {
				return str_replace(
					'Aws\\\\{$namespace}\\\\{$namespace}MultiRegionClient',
					sprintf( '%s\\\\Aws\\\\{$namespace}\\\\{$namespace}MultiRegionClient', addslashes( $prefix ) ),
					$content
				);
			}

			return $content;
		},

		/**
		 * Prefix the aws-sdk-php namespace in strings for the Sdk class.
		 *
		 * @param string $filePath The path of the current file.
		 * @param string $prefix   The prefix to be used.
		 * @param string $content  The content of the specific file.
		 *
		 * @return string The modified content.
		 */
		function( $file_path, $prefix, $content ) {
			if ( strpos( $file_path, 'aws/aws-sdk-php/src/EndpointV2/Ruleset/RulesetStandardLibrary.php' ) !== false ) {
				return str_replace(
					'Aws\\\\EndpointV2\\\\Ruleset\\\\RulesetStandardLibrary',
					sprintf( '%s\\\\Aws\\\\EndpointV2\\\\Ruleset\\\\RulesetStandardLibrary', addslashes( $prefix ) ),
					$content
				);
			}

			return $content;
		},

		/**
		 * Fix the  over-prefixed aws-sdk-php date format in the SignatureV4 class.
		 *
		 * @param string $filePath The path of the current file.
		 * @param string $prefix   The prefix to be used.
		 * @param string $content  The content of the specific file.
		 *
		 * @return string The modified content.
		 */
		function( $file_path, $prefix, $content ) {
			if ( strpos( $file_path, 'aws/aws-sdk-php/src/Signature/SignatureV4.php' ) !== false ) {
				return str_replace(
					'WPMailSMTP\\\\Vendor\\\\Ymd\\\\THis\\\\Z',
					'Ymd\\THis\\Z',
					$content
				);
			}

			return $content;
		},

	],

	/*
	 * Whitelists a list of files. Unlike the other whitelist related features, this one is about completely leaving
	 * a file untouched.
	 * Paths are relative to the configuration file unless if they are already absolute.
	 */
	'files-whitelist'            => [
		'../vendor/symfony/polyfill-mbstring/bootstrap.php',
		'../vendor/symfony/polyfill-mbstring/Resources/mb_convert_variables.php8',
		'../vendor/symfony/polyfill-intl-idn/bootstrap.php',
		'../vendor/symfony/polyfill-php72/bootstrap.php',
	],
];

/**
 * Pro plugin version dependencies.
 *
 * Should be added to the list of finders only if they exist (in lite version they don't).
 */
if ( file_exists( 'vendor/league/oauth2-client' ) ) {
	$config['finders'][] = Finder::create()
		->files()
		->in( 'vendor/league/oauth2-client' )
		->name( [ '*.php', 'LICENSE', 'composer.json' ] );
}

if ( file_exists( 'vendor/aws' ) ) {
	$config['finders'][] = Finder::create()
			->files()
			->in( 'vendor/aws' )
			->exclude( [
				'aws-sdk-php/src/S3',
				'aws-sdk-php/src/data/s3',
			] );
}

if ( file_exists( 'vendor/mtdowling/jmespath.php' ) ) {
	$config['finders'][] = Finder::create()
		->files()
		->in( 'vendor/mtdowling/jmespath.php' )
		->name( [ '*.php', 'LICENSE', 'composer.json' ] );
}

if ( file_exists( 'vendor/mk-j/php_xlsxwriter/xlsxwriter.class.php' ) ) {
	$config['finders'][] = Finder::create()
		->files()
		->in( 'vendor/mk-j/php_xlsxwriter/' )
		->name( [ 'xlsxwriter.class.php', 'LICENSE', 'composer.json' ] );
}

return $config;
