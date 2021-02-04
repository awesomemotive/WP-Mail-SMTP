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
			->in( 'vendor/google/apiclient-services/src/Google/Service/Gmail/' ),
		Finder::create()
			->files()
			->in( 'vendor/google/apiclient-services/src/Google/Service/' )
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
			->in( 'vendor/phpseclib' )
			->name( [ '*.php', 'LICENSE', 'composer.json' ] ),
		Finder::create()
			->files()
			->in(
				[
					'vendor/psr/cache',
					'vendor/psr/http-message',
					'vendor/psr/log',
				]
			)
			->name( [ '*.php', 'LICENSE', 'composer.json' ] ),
		Finder::create()
			->files()
			->in( 'vendor/sendinblue' )
			->exclude(
				[
					'test',
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
		 * Prefix the dynamic class generation in Google's apiclient lib.
		 *
		 * @param string $filePath The path of the current file.
		 * @param string $prefix   The prefix to be used.
		 * @param string $content  The content of the specific file.
		 *
		 * @return string The modified content.
		 */
		function ( $file_path, $prefix, $content ) {
			if ( strpos( $file_path, 'google/apiclient/src/Google/Http/REST.php' ) !== false ) {
				return preg_replace(
					'/(return new \$expectedClass\(\$json\);)/',
					'$expectedClass = \'WPMailSMTP\\\\\\Vendor\\\\\\\' . $expectedClass;' . PHP_EOL . '            $1',
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
				strpos( $file_path, 'google/apiclient/src/Google/Client.php' ) !== false ||
				strpos( $file_path, 'google/apiclient/src/Google/AuthHandler/AuthHandlerFactory.php' ) !== false ||
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
		 * Prefix the phpseclib namespace in strings.
		 *
		 * @param string $filePath The path of the current file.
		 * @param string $prefix   The prefix to be used.
		 * @param string $content  The content of the specific file.
		 *
		 * @return string The modified content.
		 */
		function( $file_path, $prefix, $content ) {
			if ( strpos( $file_path, 'google/apiclient/src/Google/AccessToken/Verify.php' ) !== false ) {
				return str_replace(
					'phpseclib\\\\Crypt\\\\RSA::MODE_OPENSSL',
					sprintf( '%s\\\\phpseclib\\\\Crypt\\\\RSA::MODE_OPENSSL', addslashes( $prefix ) ),
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

		/**
		 * Prefix the sendinblue namespace with array in strings.
		 *
		 * @param string $filePath The path of the current file.
		 * @param string $prefix   The prefix to be used.
		 * @param string $content  The content of the specific file.
		 *
		 * @return string The modified content.
		 */
		function( $file_path, $prefix, $content ) {
			if (
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetChildInfoApiKeys.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/CreateSender.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetAccount.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetAttributes.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetAttributesAttributes.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetContactCampaignStats.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetContactCampaignStatsClicked.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetContactCampaignStatsUnsubscriptions.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetEmailEventReport.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetExtendedContactDetailsStatistics.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetExtendedList.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetIps.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetSendersListSenders.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetSendersList.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetReports.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetProcesses.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetIpsFromSender.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetExtendedContactDetailsStatisticsClicked.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetSmsEventReport.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetTransacSmsReport.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetTransacEmailsList.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetTransacEmailContent.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetTransacBlockedContacts.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetSmsEventReport.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetSmtpTemplates.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/ObjectSerializer.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/UpdateSender.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/SendSmtpEmail.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/SendEmail.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/UpdateAttribute.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/GetExtendedContactDetailsStatisticsUnsubscriptions.php' ) !== false ||
				strpos( $file_path, 'sendinblue/api-v3-sdk/lib/Model/CreateAttribute.php' ) !== false
			) {
				return str_replace(
					'\'\\\\SendinBlue\\\\Client',
					sprintf( '\'%s\\\\SendinBlue\\\\Client', addslashes( $prefix ) ),
					$content
				);
			}

			return $content;
		},

		/**
		 * Prefix the phpseclib namespace in strings.
		 *
		 * @param string $filePath The path of the current file.
		 * @param string $prefix   The prefix to be used.
		 * @param string $content  The content of the specific file.
		 *
		 * @return string The modified content.
		 */
		function( $file_path, $prefix, $content ) {
			if ( strpos( $file_path, 'phpseclib/phpseclib/phpseclib/File/X509.php' ) !== false ) {
				return str_replace(
					'\'\\\\phpseclib\\\\File\\\\X509',
					sprintf( '\'\\\\%s\\\\phpseclib\\\\File\\\\X509', addslashes( $prefix ) ),
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

if ( file_exists( 'vendor/aws/aws-sdk-php' ) ) {
	$config['finders'][] = Finder::create()
		->files()
		->in( 'vendor/aws/aws-sdk-php' )
		->name( [ 'LICENSE.md', 'composer.json' ] );
	$config['finders'][] = Finder::create()
		->files()
		->in( 'vendor/aws/aws-sdk-php/src/Api' )
		->in( 'vendor/aws/aws-sdk-php/src/ClientSideMonitoring' )
		->in( 'vendor/aws/aws-sdk-php/src/Credentials' )
		->in( 'vendor/aws/aws-sdk-php/src/Crypto' )
		->in( 'vendor/aws/aws-sdk-php/src/data/email' )
		->in( 'vendor/aws/aws-sdk-php/src/data/sesv2' )
		->in( 'vendor/aws/aws-sdk-php/src/Endpoint' )
		->in( 'vendor/aws/aws-sdk-php/src/EndpointDiscovery' )
		->in( 'vendor/aws/aws-sdk-php/src/Exception' )
		->in( 'vendor/aws/aws-sdk-php/src/Handler' )
		->in( 'vendor/aws/aws-sdk-php/src/Multipart' )
		->in( 'vendor/aws/aws-sdk-php/src/Retry' )
		->in( 'vendor/aws/aws-sdk-php/src/Ses' )
		->in( 'vendor/aws/aws-sdk-php/src/Signature' )
		->name( [ '*.php' ] );
	$config['finders'][] = Finder::create()
		->files()
		->in( 'vendor/aws/aws-sdk-php/src/data/' )
		->name( [ 'aliases.json.php', 'endpoints.json.php', 'endpoints_prefix_history.json.php', 'manifest.json.php' ] );
	$config['finders'][] = Finder::create()
		->depth( '==0' )
		->files()
		->in( 'vendor/aws/aws-sdk-php/src' )
		->name( [ '*.php' ] );

	$config['files-whitelist'] = array_merge(
		$config['files-whitelist'],
		wms_php_scoper_get_list_of_files( '../vendor/aws/aws-sdk-php/src/data' )
	);
}

if ( file_exists( 'vendor/mtdowling/jmespath.php' ) ) {
	$config['finders'][] = Finder::create()
		->files()
		->in( 'vendor/mtdowling/jmespath.php' )
		->name( [ '*.php', 'LICENSE', 'composer.json' ] );
}

return $config;
