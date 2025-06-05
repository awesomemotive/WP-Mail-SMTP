<?php
// phpcs:ignoreFile

if ( version_compare( phpversion(), '7.4', '>=' ) && version_compare( phpversion(), '7.4', '<=' ) ) {
	echo 'Your PHP version is not correct (' . phpversion() . ')! Please use PHP 7.4 for executing this composer script.' . PHP_EOL;

	exit( 1 );
}
