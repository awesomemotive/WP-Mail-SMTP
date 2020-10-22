<?php
// phpcs:ignoreFile

if ( version_compare( phpversion(), '7.0', '>=' ) ) {
	echo 'Your PHP version is too high (' . phpversion() . ')! Please use PHP 5.6 for executing this composer script.' . PHP_EOL;

	exit( 1 );
}
