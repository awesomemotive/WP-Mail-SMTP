<?php
// phpcs:ignoreFile

if ( version_compare( phpversion(), '7.3', '<' ) ) {
	echo 'Your PHP version is too low (' . phpversion() . ')! Please use PHP 7.3 or higher for executing this composer script.' . PHP_EOL;

	exit( 1 );
}
