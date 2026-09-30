<?php

namespace WPMailSMTP\Providers\SMTP;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use WPMailSMTP\Providers\Preflight\Code;
use WPMailSMTP\Providers\Preflight\Finding;
use WPMailSMTP\Providers\Preflight\Findings;
use WPMailSMTP\Providers\Preflight\PreflightAbstract;
use WPMailSMTP\WP;

/**
 * Drives the SMTP session the send path drives, minus the mail transaction: connect, EHLO,
 * STARTTLS, EHLO, AUTH, QUIT.
 *
 * @since 4.10.0
 */
class Preflight extends PreflightAbstract {

	/**
	 * Verdicts that establish no SMTP conversation happened, leaving the port in question.
	 *
	 * A cert, encryption or credential refusal reached one, so the configured port carried it.
	 *
	 * @since 4.10.0
	 *
	 * @var string[]
	 */
	private const UNREACHED = [ self::CONNECTION_REFUSED, self::CONNECTION_TIMED_OUT, self::NO_RESPONSE ];

	/**
	 * Ports worth a greeting probe, keyed by the port the site is configured with.
	 *
	 * @since 4.10.0
	 *
	 * @var array<int, int[]>
	 */
	private const ALTERNATIVE_PORTS = [
		25  => [ 587, 465 ],
		587 => [ 465 ],
		465 => [ 587 ],
	];

	/**
	 * Seconds one greeting probe may spend, kept short enough for two to fit inside the wait the
	 * caller already allows the check.
	 *
	 * @since 4.10.0
	 *
	 * @var int
	 */
	private const ALTERNATIVE_TIMEOUT = 5;

	/**
	 * The IANA implicit-TLS submission port, and the only side information that separates a
	 * wrong Encryption setting from an unidentifiable silent socket.
	 *
	 * @since 4.10.0
	 *
	 * @var int
	 */
	private const IMPLICIT_TLS_PORT = 465;

	/**
	 * `ECONNREFUSED` as each platform's C library numbers it: Linux, BSD and macOS, Windows.
	 *
	 * The three families' numbers are disjoint and none of them is a reply code, so the union
	 * needs no platform test.
	 *
	 * @since 4.10.0
	 *
	 * @var string[]
	 */
	private const ERRNO_REFUSED = [ '111', '61', '10061' ];

	/**
	 * `ETIMEDOUT`, numbered the same three ways.
	 *
	 * @since 4.10.0
	 *
	 * @var string[]
	 */
	private const ERRNO_TIMED_OUT = [ '110', '60', '10060' ];

	/**
	 * `ENETUNREACH`, `EHOSTUNREACH` and `EADDRNOTAVAIL`, numbered the same three ways.
	 *
	 * One list because the attempt was never routed, so none of the three carries information
	 * about a configured value.
	 *
	 * @since 4.10.0
	 *
	 * @var string[]
	 */
	private const ERRNO_UNREACHABLE = [
		'101',
		'51',
		'10051',
		'113',
		'65',
		'10065',
		'99',
		'49',
		'10049',
	];

	/**
	 * Nothing is listening on host:port.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const CONNECTION_REFUSED = 'smtp_connection_refused';

	/**
	 * Packets are being dropped, which says nothing about the configuration.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const CONNECTION_TIMED_OUT = 'smtp_connection_timed_out';

	/**
	 * The host is not a usable hostname, or does not resolve.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const HOST_UNRESOLVABLE = 'smtp_host_unresolvable';

	/**
	 * The socket opened and stayed silent on the implicit-TLS port.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const NO_GREETING = 'smtp_no_greeting';

	/**
	 * The configured port answered nothing while another one on the same host greeted.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const PORT_UNREACHABLE = 'smtp_port_unreachable';

	/**
	 * The socket opened and stayed silent anywhere else.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const NO_RESPONSE = 'smtp_no_response';

	/**
	 * The server rejected the session in its greeting.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const GREETING_REJECTED = 'smtp_greeting_rejected';

	/**
	 * The Encryption setting is incompatible with what the server speaks on this port.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const ENCRYPTION_MISMATCH = 'smtp_encryption_mismatch';

	/**
	 * The server's certificate is untrusted, self-signed or expired.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const TLS_CERT_INVALID = 'smtp_tls_cert_invalid';

	/**
	 * The server's certificate does not cover the configured host.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const TLS_HOSTNAME_MISMATCH = 'smtp_tls_hostname_mismatch';

	/**
	 * The server withholds AUTH until the session is encrypted.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const ENCRYPTION_REQUIRED = 'smtp_encryption_required';

	/**
	 * The server offers no authentication this client can use.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const AUTH_NOT_SUPPORTED = 'smtp_auth_not_supported';

	/**
	 * The account requires an application-specific password.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const APP_PASSWORD_REQUIRED = 'smtp_app_password_required';

	/**
	 * The server rejected AUTH transiently, so no verdict about the credentials.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const AUTH_TEMPORARY_FAILURE = 'smtp_auth_temporary_failure';

	/**
	 * Authentication is off while the server advertises it.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const AUTH_DISABLED_BUT_OFFERED = 'smtp_auth_disabled_but_offered';

	/**
	 * A corrupted username and a corrupted password return a byte-identical 535, so no
	 * response can name one of them.
	 *
	 * @since 4.10.0
	 *
	 * @var string[]
	 */
	private const CREDENTIAL_FIELDS = [ 'user', 'pass' ];

	/**
	 * The replies that answer for the command rather than for what it carried.
	 *
	 * @since 4.10.0
	 *
	 * @var int[]
	 */
	private const SYNTAX_REPLIES = [ 500, 501, 502, 503, 504 ];

	/**
	 * Read the fields this check sends.
	 *
	 * An absent Host answers with its own verdict about the Host field, and an absent credential
	 * with one about the credential, so neither stops the run here. A port outside the range a
	 * socket accepts leaves nothing to connect to and no field to attribute it to.
	 *
	 * The password is the one field read as submitted, since `Options` stores it that way and a
	 * password may legitimately carry any character.
	 *
	 * @since 4.10.0
	 *
	 * @param array $config Mailer options as submitted.
	 *
	 * @return array|null
	 */
	protected function sanitize_config( $config ) {

		$config = array_merge(
			[
				'host'       => '',
				'port'       => '',
				'encryption' => '',
				'auth'       => false,
				'autotls'    => false,
				'user'       => '',
				'pass'       => '',
			],
			$config
		);

		$port = trim( (string) $config['port'] );

		$in_range = filter_var(
			$port,
			FILTER_VALIDATE_INT,
			[
				'options' => [
					'min_range' => 1,
					'max_range' => 65535,
				],
			]
		);

		if ( $port !== '' && $in_range === false ) {
			return null;
		}

		return [
			'host'       => sanitize_text_field( (string) $config['host'] ),
			'port'       => (int) $port,
			'encryption' => sanitize_text_field( (string) $config['encryption'] ),
			'auth'       => (bool) $config['auth'],
			'autotls'    => (bool) $config['autotls'],
			'user'       => sanitize_text_field( (string) $config['user'] ),
			'pass'       => trim( (string) $config['pass'] ),
		];
	}

	/**
	 * Run the check.
	 *
	 * @since 4.10.0
	 *
	 * @param array $config Mailer options.
	 *
	 * @return Findings
	 */
	public function run( $config ) {

		$this->require_phpmailer();

		$sanitized_config = $this->sanitize_config( $config );

		if ( $sanitized_config === null ) {
			return $this->incomplete_config();
		}

		$findings = Findings::none();

		$config   = $sanitized_config;
		$endpoint = $this->endpoint( $config );

		if ( $endpoint === null ) {
			$host_error = $this->error( self::HOST_UNRESOLVABLE, 'host' );

			$findings->add( $this->first_entry_only( $host_error, $this->host_entries( $config ) ) );

			return $findings;
		}

		// The caller's read timeout is what ends the wait, so the run asks the host for the room
		// to reach it.
		WP::set_time_limit( 0 );

		$debug   = [];
		$smtp    = $this->client( $debug );
		$session = $this->walk( $smtp, $endpoint, $config );

		$observation = [
			'error'      => $smtp->getError(),
			'last_reply' => $smtp->getLastReply(),
			'debug'      => $debug,
			'caps'       => $session['caps'],
			'port'       => $endpoint['port'],
			'encrypted'  => $session['encrypted'],
			'entries'    => $endpoint['entries'],
		];

		// A half-negotiated socket never answers a plaintext QUIT, and close() alone releases it.
		if ( $session['stage'] !== 'starttls' ) {
			$smtp->quit();
		}

		$smtp->close();

		if ( $session['stage'] !== null ) {
			$findings->add(
				$this->port_verdict( $this->classify( $session['stage'], $observation ), $endpoint )
			);

			return $findings;
		}

		$offered = $this->auth_gap_finding( $session['caps'], ! empty( $config['auth'] ) );

		if ( $offered !== null ) {
			$findings->add( $offered );
		}

		return $findings;
	}

	/**
	 * Turn one observation into a finding.
	 *
	 * The numeric signal is three unrelated namespaces in one field, so the stage is the
	 * primary discriminator and nothing branches on an error string.
	 *
	 * @since 4.10.0
	 *
	 * @param string $stage       Stage that failed: connect, ehlo, starttls, ehlo_post_tls
	 *                            or auth.
	 * @param array  $observation Snapshot taken before the connection was closed, keyed
	 *                            error, last_reply, debug, caps, port, encrypted, entries.
	 *
	 * @return Finding
	 */
	protected function classify( $stage, $observation ) {

		$entries = isset( $observation['entries'] ) ? (int) $observation['entries'] : 1;

		if ( $stage === 'connect' ) {
			return $this->first_entry_only( $this->classify_connect( $observation ), $entries );
		}

		if ( $stage === 'starttls' ) {
			return $this->first_entry_only( $this->classify_starttls( $observation ), $entries );
		}

		if ( $stage === 'auth' ) {
			return $this->first_entry_only( $this->classify_auth( $observation ), $entries );
		}

		return $this->inconclusive( Code::UNEXPECTED_RESPONSE );
	}

	/**
	 * Withdraw a blocking verdict that only covers the Host entry this run probed.
	 *
	 * `PHPMailer::smtpConnect()` walks every entry and skips the ones it cannot use, so a first
	 * entry that fails says nothing about a send the later entries would carry.
	 *
	 * @since 4.10.0
	 *
	 * @param Finding $finding Verdict about the first entry.
	 * @param int     $entries Entries the Host field holds.
	 *
	 * @return Finding
	 */
	private function first_entry_only( $finding, $entries ) {

		if ( $entries < 2 || ! $finding->is_blocking() ) {
			return $finding;
		}

		return $this->inconclusive( $finding->get_code() );
	}

	/**
	 * Walk the session, stopping at the first stage that fails.
	 *
	 * @since 4.10.0
	 *
	 * @param SMTP  $smtp     Client to drive.
	 * @param array $endpoint Parsed endpoint.
	 * @param array $config   Mailer options.
	 *
	 * @return array Keyed stage, caps, encrypted.
	 */
	private function walk( $smtp, $endpoint, $config ) {

		$session = [
			'stage'     => null,
			'caps'      => null,
			'encrypted' => $endpoint['secure'] === 'ssl',
		];

		if ( ! $smtp->connect( $endpoint['address'], $endpoint['port'], self::TIMEOUT ) ) {
			$session['stage'] = 'connect';

			return $session;
		}

		if ( ! $smtp->hello( $this->helo_name() ) ) {
			$session['stage'] = 'ehlo';

			return $session;
		}

		$session['caps'] = $smtp->getServerExtList();

		if ( ! $this->wants_tls( $endpoint, $config, $session['caps'] ) ) {
			return $this->attempt_auth( $smtp, $session, $config );
		}

		return $this->upgrade( $smtp, $session, $config );
	}

	/**
	 * Encrypt the session, then continue, or stop at the stage that failed.
	 *
	 * @since 4.10.0
	 *
	 * @param SMTP  $smtp    Client to drive.
	 * @param array $session Session so far.
	 * @param array $config  Mailer options.
	 *
	 * @return array
	 */
	private function upgrade( $smtp, $session, $config ) {

		if ( ! $smtp->startTLS() ) {
			$session['stage'] = 'starttls';

			return $session;
		}

		// AUTH appears in the capability list only after TLS on most real servers.
		if ( ! $smtp->hello( $this->helo_name() ) ) {
			$session['stage'] = 'ehlo_post_tls';

			return $session;
		}

		$session['caps']      = $smtp->getServerExtList();
		$session['encrypted'] = true;

		return $this->attempt_auth( $smtp, $session, $config );
	}

	/**
	 * Authenticate once, if authentication is configured.
	 *
	 * @since 4.10.0
	 *
	 * @param SMTP  $smtp    Client to drive.
	 * @param array $session Session so far.
	 * @param array $config  Mailer options.
	 *
	 * @return array
	 */
	private function attempt_auth( $smtp, $session, $config ) {

		if ( empty( $config['auth'] ) ) {
			return $session;
		}

		// Never retried: two rejected attempts locked a live host out of correct credentials.
		if ( ! $smtp->authenticate( $config['user'], $config['pass'] ) ) {
			$session['stage'] = 'auth';
		}

		return $session;
	}

	/**
	 * Turn a verdict about a port that answered nothing into one about the port, where another
	 * port on the same host greets.
	 *
	 * Only the first Host entry is probed, so a Host field holding several leaves the question
	 * open rather than answering it about one of them.
	 *
	 * @since 4.10.0
	 *
	 * @param Finding $finding  Verdict the session produced.
	 * @param array   $endpoint Parsed endpoint.
	 *
	 * @return Finding
	 */
	protected function port_verdict( $finding, $endpoint ) {

		if ( ! in_array( $finding->get_code(), self::UNREACHED, true ) || $endpoint['entries'] > 1 ) {
			return $finding;
		}

		$configured = $endpoint['port'];
		$candidates = self::ALTERNATIVE_PORTS[ $configured ] ?? [ 587, self::IMPLICIT_TLS_PORT ];

		foreach ( array_diff( $candidates, [ $configured ] ) as $port ) {
			if ( $this->greets( $endpoint['host'], $port ) ) {
				return $this->error( self::PORT_UNREACHABLE, 'port' );
			}
		}

		return $finding;
	}

	/**
	 * Whether one port answers with a greeting.
	 *
	 * Reads the greeting and closes: PHPMailer below 6.3 does not check the reply code itself,
	 * so a connection that opened is not on its own an answer.
	 *
	 * @since 4.10.0
	 *
	 * @param string $host Bare host, without a transport prefix or a port.
	 * @param int    $port Port to probe.
	 *
	 * @return bool
	 */
	protected function greets( $host, $port ) {

		$smtp = new SMTP();

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$smtp->do_debug  = SMTP::DEBUG_OFF;
		$smtp->Timeout   = self::ALTERNATIVE_TIMEOUT;
		$smtp->Timelimit = self::ALTERNATIVE_TIMEOUT;
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$address = $port === self::IMPLICIT_TLS_PORT ? 'ssl://' . $host : $host;

		if ( ! $smtp->connect( $address, $port, self::ALTERNATIVE_TIMEOUT ) ) {
			return false;
		}

		$greeted = strpos( (string) $smtp->getLastReply(), '220' ) === 0;

		// No QUIT: a greeting is the whole question, and the reply to one would cost a second wait.
		$smtp->close();

		return $greeted;
	}

	/**
	 * Classify a failure to open a usable session.
	 *
	 * @since 4.10.0
	 *
	 * @param array $observation Snapshot.
	 *
	 * @return Finding
	 */
	private function classify_connect( $observation ) {

		$errno = $this->classify_errno( $this->error_member( $observation, 'smtp_code' ) );

		if ( $errno !== null ) {
			return $errno;
		}

		if ( strpos( $this->error_member( $observation, 'smtp_code_ex' ), 'getaddrinfo' ) !== false ) {
			return $this->error( self::HOST_UNRESOLVABLE, 'host' );
		}

		// An implicit-TLS failure keeps its reason only in the debug stream.
		$crypto = $this->classify_crypto( implode( "\n", $observation['debug'] ) );

		if ( $crypto !== null ) {
			// The handshake is the connection here, so a certificate note cannot read as connected.
			return $crypto->get_code() === self::TLS_CERT_INVALID
				? $this->inconclusive( self::TLS_CERT_INVALID )
				: $crypto;
		}

		if ( $this->error_member( $observation, 'error' ) !== '' ) {
			return $this->inconclusive( Code::UNEXPECTED_RESPONSE );
		}

		return $this->classify_greeting( $observation );
	}

	/**
	 * Read a verdict out of the errno the failed connect reported.
	 *
	 * @since 4.10.0
	 *
	 * @param string $error Numeric signal from the failed connect.
	 *
	 * @return Finding|null Null when the errno is not one of the recognized failures.
	 */
	private function classify_errno( $error ) {

		// A rejected connection is as often a firewall in front of the right port as a wrong one.
		if ( in_array( $error, self::ERRNO_REFUSED, true ) ) {
			return $this->error( self::CONNECTION_REFUSED );
		}

		if ( in_array( $error, self::ERRNO_TIMED_OUT, true ) ) {
			return $this->inconclusive( self::CONNECTION_TIMED_OUT );
		}

		if ( in_array( $error, self::ERRNO_UNREACHABLE, true ) ) {
			return $this->inconclusive( Code::NETWORK_ERROR );
		}

		return null;
	}

	/**
	 * Classify an empty-error connect failure, which is either a rejected greeting or silence.
	 *
	 * @since 4.10.0
	 *
	 * @param array $observation Snapshot.
	 *
	 * @return Finding
	 */
	private function classify_greeting( $observation ) {

		$greeting = $this->greeting_code( $observation );

		if ( $greeting >= 400 && $greeting < 500 ) {
			return $this->inconclusive( Code::PROVIDER_UNAVAILABLE );
		}

		if ( $greeting >= 500 ) {
			return $this->error( self::GREETING_REJECTED );
		}

		if ( $greeting !== 0 ) {
			return $this->inconclusive( Code::UNEXPECTED_RESPONSE );
		}

		if ( strpos( implode( "\n", $observation['debug'] ), 'Connection: opened' ) === false ) {
			return $this->inconclusive( Code::UNEXPECTED_RESPONSE );
		}

		// On 465 the socket reports opened only once TLS is up, so an encrypted one proves the
		// setting right however long the greeting then takes.
		return $observation['port'] === self::IMPLICIT_TLS_PORT && empty( $observation['encrypted'] )
			? $this->error( self::NO_GREETING, 'encryption' )
			: $this->inconclusive( self::NO_RESPONSE );
	}

	/**
	 * Classify a STARTTLS failure.
	 *
	 * @since 4.10.0
	 *
	 * @param array $observation Snapshot.
	 *
	 * @return Finding
	 */
	private function classify_starttls( $observation ) {

		// Unlike the implicit-TLS path, STARTTLS keeps the handshake reason in the error.
		$crypto = $this->classify_crypto( $this->error_member( $observation, 'detail' ) );

		if ( $crypto !== null ) {
			return $crypto;
		}

		$reply = $this->reply_code( $observation );

		if ( $reply !== null && $reply >= 500 && ! $this->has_cap( $observation['caps'], 'STARTTLS' ) ) {
			return $this->error( self::ENCRYPTION_MISMATCH, 'encryption' );
		}

		return $this->inconclusive( Code::UNEXPECTED_RESPONSE );
	}

	/**
	 * Classify an AUTH failure.
	 *
	 * @since 4.10.0
	 *
	 * @param array $observation Snapshot.
	 *
	 * @return Finding
	 */
	private function classify_auth( $observation ) {

		$reply = $this->reply_code( $observation );

		// PHPMailer blind-fires AUTH LOGIN at a HELO-only server, so only a refusal of the
		// command itself is about the absent extension rather than the credentials.
		if ( $this->has_cap( $observation['caps'], 'HELO' ) && $this->absent_extension( $reply ) ) {
			return $this->error( self::AUTH_NOT_SUPPORTED, 'auth' );
		}

		if ( $reply === null ) {
			if (
				! $this->has_cap( $observation['caps'], 'AUTH' ) &&
				$this->has_cap( $observation['caps'], 'STARTTLS' ) &&
				empty( $observation['encrypted'] )
			) {
				return $this->error( self::ENCRYPTION_REQUIRED, 'encryption' );
			}

			return $this->error( self::AUTH_NOT_SUPPORTED, 'auth' );
		}

		if ( $reply >= 400 && $reply < 500 ) {
			return $this->inconclusive( self::AUTH_TEMPORARY_FAILURE );
		}

		if ( $reply >= 500 ) {
			return $this->classify_auth_rejection( $reply, $this->error_member( $observation, 'smtp_code_ex' ) );
		}

		return $this->inconclusive( Code::UNEXPECTED_RESPONSE );
	}

	/**
	 * Whether an AUTH failure carries nothing beyond the extension being missing.
	 *
	 * A null reply is PHPMailer's own client-side refusal; the enumerated replies are the server
	 * answering for the command, which a server that never advertised AUTH has to.
	 *
	 * @since 4.10.0
	 *
	 * @param int|null $reply Reply code, or null when no reply was read.
	 *
	 * @return bool
	 */
	private function absent_extension( $reply ) {

		return $reply === null || in_array( $reply, self::SYNTAX_REPLIES, true );
	}

	/**
	 * Classify a permanent AUTH rejection.
	 *
	 * RFC 4954 defines one reply that means the credentials are wrong, so every other reply
	 * either names the field it does identify or names nothing.
	 *
	 * @since 4.10.0
	 *
	 * @param int    $reply    Reply code.
	 * @param string $enhanced Enhanced status code, empty when the server sent none.
	 *
	 * @return Finding
	 */
	private function classify_auth_rejection( $reply, $enhanced ) {

		// Google reports the app-password reply as 5.7.9 and documents it as 5.7.90.
		if ( $reply === 534 && strpos( $enhanced, '5.7.9' ) === 0 ) {
			return $this->error( self::APP_PASSWORD_REQUIRED, 'pass' );
		}

		if ( $reply === 535 || $enhanced === '5.7.8' ) {
			return $this->error( Code::AUTH_FAILED, self::CREDENTIAL_FIELDS );
		}

		if ( $reply === 538 || $enhanced === '5.7.11' ) {
			return $this->error( self::ENCRYPTION_REQUIRED, 'encryption' );
		}

		if ( $reply === 504 || $enhanced === '5.5.4' ) {
			return $this->error( self::AUTH_NOT_SUPPORTED, 'auth' );
		}

		return $this->inconclusive( Code::UNEXPECTED_RESPONSE );
	}

	/**
	 * Read a TLS diagnosis out of whichever carrier holds it.
	 *
	 * Matched on the stable OpenSSL identifier, never on the version-dependent prose.
	 *
	 * @since 4.10.0
	 *
	 * @param string $text Debug stream or error detail.
	 *
	 * @return Finding|null
	 */
	private function classify_crypto( $text ) {

		if ( strpos( $text, 'error:0A00010B' ) !== false ) {
			return $this->error( self::ENCRYPTION_MISMATCH, 'encryption' );
		}

		if ( strpos( $text, 'Peer certificate CN=' ) !== false && preg_match( '/error:0A[\dA-Fa-f]{6}/', $text ) !== 1 ) {
			return $this->error( self::TLS_HOSTNAME_MISMATCH, 'host' );
		}

		// Sites relax peer verification from `phpmailer_init` and send fine, and no form field
		// can fix a server's certificate.
		if ( strpos( $text, 'error:0A000086' ) !== false ) {
			return $this->warning( self::TLS_CERT_INVALID );
		}

		return null;
	}

	/**
	 * Report a session that authenticated with nothing against a server that offers authentication.
	 *
	 * @since 4.10.0
	 *
	 * @param array|null $caps Capability set from the last EHLO.
	 * @param bool       $auth Whether authentication is configured.
	 *
	 * @return Finding|null Null when the two agree.
	 */
	protected function auth_gap_finding( $caps, $auth ) {

		if ( $auth || ! $this->has_cap( $caps, 'AUTH' ) ) {
			return null;
		}

		// Whether an unauthenticated relay is accepted depends on server policy we cannot see.
		return $this->warning( self::AUTH_DISABLED_BUT_OFFERED, 'auth' );
	}

	/**
	 * Parse the first Host entry into the address, port and transport the send path derives
	 * from it, and count the entries after it.
	 *
	 * The Host field is a semicolon-separated list whose entries may carry a scheme prefix and
	 * an inline port, either of which overrides the Encryption and Port fields. Only the first
	 * entry is parsed, while `PHPMailer::smtpConnect()` walks the whole list.
	 *
	 * @since 4.10.0
	 *
	 * @param array $config Mailer options.
	 *
	 * @return array|null Null when the first entry cannot be used as a host.
	 */
	private function endpoint( $config ) {

		$raw_host = $config['host'];
		$entries  = explode( ';', $raw_host );
		$parts    = [];

		if ( ! preg_match( '#^(?:(ssl|tls)://)?(.+?)(?::(\d+))?$#', trim( (string) reset( $entries ) ), $parts ) ) {
			return null;
		}

		if ( ! PHPMailer::isValidHost( $parts[2] ) ) {
			return null;
		}

		$secure = $this->secure_transport( $parts[1], $config['encryption'] );

		return [
			'address'  => ( $secure === 'ssl' ? 'ssl://' : '' ) . $parts[2],
			'host'     => $parts[2],
			'port'     => $this->resolve_port( $config, $parts ),
			'secure'   => $secure,
			'tls'      => $secure === 'tls',
			'raw_host' => $raw_host,
			'entries'  => count( $entries ),
		];
	}

	/**
	 * Count the entries the Host field holds.
	 *
	 * @since 4.10.0
	 *
	 * @param array $config Mailer options.
	 *
	 * @return int
	 */
	private function host_entries( $config ) {

		return count( explode( ';', $config['host'] ) );
	}

	/**
	 * Resolve the transport, letting a scheme prefix on the host entry win.
	 *
	 * @since 4.10.0
	 *
	 * @param string $prefix     Scheme prefix from the host entry, or an empty string.
	 * @param string $encryption Encryption field value.
	 *
	 * @return string One of ssl, tls, or an empty string.
	 */
	private function secure_transport( $prefix, $encryption ) {

		if ( $prefix !== '' ) {
			return $prefix;
		}

		return in_array( $encryption, [ 'ssl', 'tls' ], true ) ? $encryption : '';
	}

	/**
	 * Resolve the port, letting an inline port on the host entry win.
	 *
	 * @since 4.10.0
	 *
	 * @param array $config Mailer options.
	 * @param array $parts  Host-entry match groups.
	 *
	 * @return int
	 */
	private function resolve_port( $config, $parts ) {

		$inline = isset( $parts[3] ) ? (int) $parts[3] : 0;

		if ( $inline > 0 && $inline < 65536 ) {
			return $inline;
		}

		return isset( $config['port'] ) ? (int) $config['port'] : 0;
	}

	/**
	 * Whether the session should be upgraded, mirroring the send path's AutoTLS condition.
	 *
	 * @since 4.10.0
	 *
	 * @param array      $endpoint Parsed endpoint.
	 * @param array      $config   Mailer options.
	 * @param array|null $caps     Capability set from the first EHLO.
	 *
	 * @return bool
	 */
	private function wants_tls( $endpoint, $config, $caps ) {

		if ( $endpoint['tls'] ) {
			return true;
		}

		// PHPMailer compares the whole unparsed Host value, so `localhost:1025` is not exempt.
		return ! empty( $config['autotls'] )
			&& $endpoint['secure'] !== 'ssl'
			&& defined( 'OPENSSL_ALGO_SHA256' )
			&& $endpoint['raw_host'] !== 'localhost'
			&& $this->has_cap( $caps, 'STARTTLS' );
	}

	/**
	 * Build the bounded, level-3 client.
	 *
	 * @since 4.10.0
	 *
	 * @param array $debug Collector for the debug stream, by reference.
	 *
	 * @return SMTP
	 */
	private function client( &$debug ) {

		$smtp = new SMTP();

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$smtp->do_debug  = SMTP::DEBUG_CONNECTION;
		$smtp->Timeout   = self::TIMEOUT;
		$smtp->Timelimit = self::TIMEOUT;

		$smtp->Debugoutput = function ( $line ) use ( &$debug ) {

			$debug[] = (string) $line;
		};
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		return $smtp;
	}

	/**
	 * Get the name to greet the server with, replicating PHPMailer's protected resolution.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	private function helo_name() {

		$candidates = [
			isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '',
			(string) gethostname(),
			php_uname( 'n' ),
		];

		foreach ( $candidates as $candidate ) {
			if ( PHPMailer::isValidHost( $candidate ) ) {
				return $candidate;
			}
		}

		return 'localhost.localdomain';
	}

	/**
	 * Load the client WordPress ships, which the plugin does not bundle.
	 *
	 * @since 4.10.0
	 */
	private function require_phpmailer() {

		if ( ! class_exists( PHPMailer::class, false ) ) {
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
		}

		if ( ! class_exists( Exception::class, false ) ) {
			require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
		}

		if ( ! class_exists( SMTP::class, false ) ) {
			require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
		}
	}

	/**
	 * Read one member of the snapshotted error array as a string.
	 *
	 * @since 4.10.0
	 *
	 * @param array  $observation Snapshot.
	 * @param string $member      Member name.
	 *
	 * @return string
	 */
	private function error_member( $observation, $member ) {

		if ( ! isset( $observation['error'][ $member ] ) ) {
			return '';
		}

		return is_scalar( $observation['error'][ $member ] ) ? (string) $observation['error'][ $member ] : '';
	}

	/**
	 * Read the numeric signal only when it is an SMTP reply code.
	 *
	 * The same member also carries OS errnos and PHP error levels, as strings.
	 *
	 * @since 4.10.0
	 *
	 * @param array $observation Snapshot.
	 *
	 * @return int|null
	 */
	private function reply_code( $observation ) {

		if ( ! isset( $observation['error']['smtp_code'] ) || ! is_int( $observation['error']['smtp_code'] ) ) {
			return null;
		}

		return $observation['error']['smtp_code'];
	}

	/**
	 * Read the greeting the server sent, if it sent one.
	 *
	 * The debug stream is consulted first because a 554 greeting is overwritten in the reply
	 * buffer by the QUIT the client library issues itself.
	 *
	 * @since 4.10.0
	 *
	 * @param array $observation Snapshot.
	 *
	 * @return int Zero when the server said nothing.
	 */
	private function greeting_code( $observation ) {

		foreach ( $observation['debug'] as $line ) {
			$match = [];

			if ( preg_match( '/SERVER -> CLIENT: (\d{3})/', (string) $line, $match ) === 1 ) {
				return (int) $match[1];
			}
		}

		return (int) substr( (string) $observation['last_reply'], 0, 3 );
	}

	/**
	 * Whether the server advertised one capability.
	 *
	 * @since 4.10.0
	 *
	 * @param array|null $caps Capability set.
	 * @param string     $name Capability name.
	 *
	 * @return bool
	 */
	private function has_cap( $caps, $name ) {

		return is_array( $caps ) && array_key_exists( $name, $caps );
	}
}
