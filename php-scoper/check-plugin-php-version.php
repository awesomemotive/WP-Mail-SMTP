<?php
// phpcs:ignoreFile

if ( version_compare( phpversion(), '7.2', '>=' ) && version_compare( phpversion(), '7.2', '<=' ) ) {
	echo 'Your PHP version is not correct (' . phpversion() . ')! Please use PHP 7.2 for executing this composer script.' . PHP_EOL;

	exit( 1 );
}
