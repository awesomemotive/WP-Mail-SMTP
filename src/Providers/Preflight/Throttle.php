<?php

namespace WPMailSMTP\Providers\Preflight;

/**
 * Caps how fast one caller can drive outbound provider calls.
 *
 * The route's capability gate is what keeps strangers out, so this only limits abuse.
 *
 * @since 4.10.0
 */
class Throttle {

	/**
	 * Calls allowed per bucket per window.
	 *
	 * @since 4.10.0
	 *
	 * @var int
	 */
	const LIMIT = 20;

	/**
	 * Bucket lifetime, in seconds.
	 *
	 * @since 4.10.0
	 *
	 * @var int
	 */
	private const WINDOW = MINUTE_IN_SECONDS;

	/**
	 * Whether the bucket has room for one more call.
	 *
	 * @since 4.10.0
	 *
	 * @param string $key Bucket key.
	 *
	 * @return bool
	 */
	public function allows( $key ) {

		return (int) get_transient( $this->transient( $key ) ) < self::LIMIT;
	}

	/**
	 * Count one call against the bucket.
	 *
	 * @since 4.10.0
	 *
	 * @param string $key Bucket key.
	 *
	 * @return void
	 */
	public function record( $key ) {

		$name  = $this->transient( $key );
		$count = (int) get_transient( $name );

		set_transient( $name, $count + 1, self::WINDOW );
	}

	/**
	 * Get the transient name backing one bucket.
	 *
	 * @since 4.10.0
	 *
	 * @param string $key Bucket key.
	 *
	 * @return string
	 */
	private function transient( $key ) {

		return 'wp_mail_smtp_preflight_' . md5( $key );
	}
}
