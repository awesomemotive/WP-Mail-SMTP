<?php

namespace WPMailSMTP\Admin;

use WPMailSMTP\ConnectionInterface;
use WPMailSMTP\DomainCheckState;
use WPMailSMTP\Helpers\Helpers;

/**
 * Class for interacting with the Domain Checker API.
 *
 * @since 2.6.0
 */
class DomainChecker {

	/**
	 * The domain checker API endpoint.
	 *
	 * @since 2.6.0
	 */
	const ENDPOINT = 'https://connect.wpmailsmtp.com/domain-check/';

	/**
	 * The API results.
	 *
	 * @since 2.6.0
	 *
	 * @var array
	 */
	private $results;

	/**
	 * The plugin mailer slug.
	 *
	 * @since 2.7.0
	 *
	 * @var string
	 */
	protected $mailer;

	/**
	 * The From email address that was checked.
	 *
	 * @since 4.10.1
	 *
	 * @var string
	 */
	protected $from_email;

	/**
	 * The optional sending domain that was checked.
	 *
	 * @since 4.10.1
	 *
	 * @var string
	 */
	protected $sending_domain;

	/**
	 * The connection the check was run for.
	 *
	 * @since 4.10.0
	 *
	 * @var ConnectionInterface
	 */
	protected $connection;

	/**
	 * Verify the domain for the provided mailer and email address and save the API results.
	 *
	 * @since 2.6.0
	 * @since 4.10.0 Added the $connection parameter.
	 *
	 * @param string                   $mailer         The plugin mailer.
	 * @param string                   $email          The email address from which the domain will be extracted.
	 * @param string                   $sending_domain The optional sending domain to check the domain records for.
	 * @param ConnectionInterface|null $connection     The connection being checked. Defaults to the primary one.
	 */
	public function __construct( $mailer, $email, $sending_domain = '', ?ConnectionInterface $connection = null ) {

		$this->mailer         = $mailer;
		$this->from_email     = $email;
		$this->sending_domain = $sending_domain;

		$this->connection = $connection ?? wp_mail_smtp()->get_connections_manager()->get_primary_connection();

		$params = [
			'mailer' => $mailer,
			'email'  => base64_encode( $email ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			'domain' => $sending_domain,
		];

		$response = wp_remote_get(
			add_query_arg( $params, self::ENDPOINT ),
			[
				'user-agent' => Helpers::get_default_user_agent(),
			]
		);

		if ( is_wp_error( $response ) ) {
			$this->results = [
				'success' => false,
				'message' => method_exists( $response, 'get_error_message' ) ?
					$response->get_error_message() :
					esc_html__( 'Something went wrong. Please try again later.', 'wp-mail-smtp' ),
				'checks'  => [],
			];
		} else {
			$this->results = json_decode( wp_remote_retrieve_body( $response ), true );
		}

		DomainCheckState::record( $this, $this->connection );
	}

	/**
	 * Simple getter for the API results.
	 *
	 * @since 2.6.0
	 *
	 * @return array
	 */
	public function get_results() {
		return $this->results;
	}

	/**
	 * Simple getter for the checked mailer.
	 *
	 * @since 4.10.1
	 *
	 * @return string
	 */
	public function get_mailer() {

		return $this->mailer;
	}

	/**
	 * Simple getter for the checked From email address.
	 *
	 * @since 4.10.1
	 *
	 * @return string
	 */
	public function get_from_email() {

		return $this->from_email;
	}

	/**
	 * Simple getter for the checked sending domain.
	 *
	 * @since 4.10.1
	 *
	 * @return string
	 */
	public function get_sending_domain() {

		return $this->sending_domain;
	}

	/**
	 * Simple getter for the checked connection.
	 *
	 * @since 4.10.0
	 *
	 * @return ConnectionInterface
	 */
	public function get_connection() {

		return $this->connection;
	}

	/**
	 * Check if the domain checker has found any errors.
	 *
	 * @since 2.6.0
	 *
	 * @return bool
	 */
	public function has_errors() {

		if ( empty( $this->results['success'] ) ) {
			return true;
		}

		if ( empty( $this->results['checks'] ) ) {
			return false;
		}

		$has_error = false;

		foreach ( $this->results['checks'] as $check ) {
			if ( $check['state'] === 'error' ) {
				$has_error = true;
				break;
			}
		}

		return $has_error;
	}

	/**
	 * Check if the domain checker has not found any errors or warnings.
	 *
	 * @since 2.6.0
	 *
	 * @return bool
	 */
	public function no_issues() {

		if ( empty( $this->results['success'] ) ) {
			return false;
		}

		$no_issues = true;

		foreach ( $this->results['checks'] as $check ) {
			if ( in_array( $check['state'], [ 'error', 'warning' ], true ) ) {
				$no_issues = false;
				break;
			}
		}

		return $no_issues;
	}

	/**
	 * Check if the domain checker support mailer.
	 *
	 * @since 2.7.0
	 *
	 * @return bool
	 */
	public function is_supported_mailer() {

		return self::is_mailer_supported( $this->mailer );
	}

	/**
	 * Check if the domain checker supports a mailer.
	 *
	 * @since 4.10.1
	 *
	 * @param string $mailer The plugin mailer slug.
	 *
	 * @return bool
	 */
	private static function is_mailer_supported( $mailer ) {

		return ! in_array( $mailer, [ 'mail', 'pepipostapi' ], true );
	}

	/**
	 * Get the domain checker results html.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function get_results_html() {

		return self::render_results( $this->get_results(), $this->mailer );
	}

	/**
	 * Get the HTML for domain check results.
	 *
	 * @since 4.10.1
	 *
	 * @param array  $results Domain check API results.
	 * @param string $mailer  The plugin mailer slug the check ran for.
	 *
	 * @return string
	 */
	public static function render_results( $results, $mailer ) {

		$allowed_html = [
			'b' => [],
			'i' => [],
			'a' => [
				'href'   => [],
				'target' => [],
				'rel'    => [],
			],
		];

		ob_start();
		?>
		<div id="wp-mail-smtp-domain-check-details">
			<h2><?php esc_html_e( 'Domain Check Results', 'wp-mail-smtp' ); ?></h2>

			<?php if ( empty( $results['success'] ) ) : ?>
				<div class="notice-inline <?php echo self::is_mailer_supported( $mailer ) ? 'notice-error' : 'notice-warning'; ?>">
					<p><?php echo wp_kses( $results['message'], $allowed_html ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $results['checks'] ) ) : ?>
				<div class="wp-mail-smtp-domain-check-details-check-list">
					<?php foreach ( $results['checks'] as $check ) : ?>
						<div class="wp-mail-smtp-domain-check-details-check-list-item">
							<img src="<?php echo esc_url( wp_mail_smtp()->assets_url . '/images/icons/' . esc_attr( $check['state'] ) . '.svg' ); ?>" class="wp-mail-smtp-domain-check-details-check-list-item-icon" alt="<?php printf( /* translators: %s - item state name. */ esc_attr__( '%s icon', 'wp-mail-smtp' ), esc_attr( $check['state'] ) ); ?>">
							<div class="wp-mail-smtp-domain-check-details-check-list-item-content">
								<h3><?php echo esc_html( $check['type'] ); ?></h3>
								<p><?php echo wp_kses( $check['message'], $allowed_html ); ?></p>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
