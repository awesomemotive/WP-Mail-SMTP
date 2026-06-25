<?php

namespace WPMailSMTP\Admin\EmailSendingErrors;

use WPMailSMTP\EmailSendingDebug;
use WPMailSMTP\Options;
use WPMailSMTP\ConnectionInterface;

/**
 * Persistent error banner shown across WP Mail SMTP admin pages whenever a real
 * send fails, with severity styling, troubleshooting links, an inline error log,
 * and a Need Help section.
 *
 * @since 4.9.0
 */
class EmailSendingErrors {

	/**
	 * Registry instance for doc-url lookups.
	 *
	 * @since 4.9.0
	 *
	 * @var Registry
	 */
	private $registry;

	/**
	 * Constructor.
	 *
	 * @since 4.9.0
	 *
	 * @param Registry|null $registry Optional Registry override.
	 */
	public function __construct( $registry = null ) {

		$this->registry = $registry instanceof Registry ? $registry : new Registry();
	}

	/**
	 * Register hooks.
	 *
	 * @since 4.9.0
	 */
	public function hooks() {

		add_action( 'wp_mail_smtp_admin_pages_before_content', [ $this, 'render_error_banner' ] );
		add_action( 'admin_notices', [ $this, 'render_global_error_notice' ] );

		add_action( 'wp_ajax_wp_mail_smtp_email_sending_errors_dismiss', [ $this, 'ajax_dismiss' ] );
	}

	/**
	 * Render the in-plugin banner on every WP Mail SMTP admin page.
	 *
	 * Renders the primary connection's record as a full banner (when set) and
	 * each additional connection's record as a stacked one-liner. Suppressed
	 * when no records exist or the current user lacks the manage-options cap.
	 *
	 * @since 4.9.0
	 */
	public function render_error_banner() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded -- Linear gate chain over context (capability, multisite, test-tab, AC-edit). Splitting hurts readability.

		if ( ! current_user_can( wp_mail_smtp()->get_capability_manage_options() ) ) {
			return;
		}

		// Read-only sticky-banner gate during admin render. Submission nonce is verified by TestTab::process_post() before any side effects.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended
		$is_test_submission = $this->is_test_tab() && isset( $_POST['wp-mail-smtp'] );

		// Respect the "hide delivery errors" option, except on a fresh test
		// email submission — the user just ran a test and expects to see its
		// result regardless of the global suppression.
		if ( ! $is_test_submission && ! wp_mail_smtp()->get_admin()->is_error_delivery_notice_enabled() ) {
			return;
		}

		// Multisite network admin shows an aggregated view in a dedicated handler —
		// this notice is per-site only.
		if ( is_network_admin() ) {
			return;
		}

		$all = EmailSendingDebug::get();

		if ( empty( $all ) ) {
			return;
		}

		// Test Email tab always starts fresh on initial load — the banner only
		// surfaces after the user actually submits a test send. Read-only
		// presence check; submission nonce is verified by TestTab::process_post().
		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended
		if ( $this->is_test_tab() && ! isset( $_POST['wp-mail-smtp'] ) ) {
			return;
		}

		// Which connection's record gets the primary (full banner) slot on this
		// page: the AC edit-view's `connection_id`, the just-tested connection on
		// the Test Email tab, or 'primary' everywhere else.
		$connection_id = $this->get_primary_banner_connection_id();

		// Render the full banner — except on the Connections list page, where
		// primary failures route to the one-liner notice and the full banner is
		// reserved for the per-connection edit view.
		if (
			isset( $all[ $connection_id ] ) &&
			! ( $this->is_connections_tab() && $connection_id === 'primary' )
		) {
			$this->print_primary_banner(
				$connection_id === 'primary' ? 'primary' : 'additional',
				$all[ $connection_id ],
				$this->get_connection_name( $connection_id ),
				$connection_id
			);

			unset( $all[ $connection_id ] );
		}

		// AC edit page and Test Email tab are connection-focused surfaces:
		// they show only the active connection's banner. Skip stacking the
		// one-liner notices for every other failing connection. Read-only
		// presence check; the connection_id GET param is a navigation key.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if (
			( $this->is_connections_tab() && isset( $_GET['connection_id'] ) ) ||
			$this->is_test_tab()
		) {
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Primary was either rendered above (and removed from $all) or is
		// suppressed on the Connections list page. Either way, the loop below
		// only handles additional connections.
		unset( $all['primary'] );

		foreach ( $all as $connection_id => $record ) {
			$connection = wp_mail_smtp()->get_connections_manager()->get_connection( $connection_id, false );

			if ( $connection !== false ) {
				$this->print_additional_connection_note( $record, $connection );
			}
		}
	}

	/**
	 * Render the cross-admin one-liner notice on non–WP Mail SMTP admin pages.
	 *
	 * Outputs plain WP core `<div class="notice notice-error">…</div>` markup
	 * so it inherits standard admin-notice styling. Only renders when at least
	 * one record has `status: failed` AND `context: regular` — test-context
	 * failures stay scoped to the in-plugin banner.
	 *
	 * @since 4.9.0
	 */
	public function render_global_error_notice() {

		// Multisite network admin shows an aggregated view in a dedicated handler;
		// this notice is per-site only.
		if (
			! current_user_can( wp_mail_smtp()->get_capability_manage_options() ) ||
			! wp_mail_smtp()->get_admin()->is_error_delivery_notice_enabled() ||
			wp_mail_smtp()->get_admin()->is_admin_page() ||
			is_network_admin()
		) {
			return;
		}

		$all  = EmailSendingDebug::get();
		$live = array_filter(
			$all,
			static function ( $record ) {

				return isset( $record['status'], $record['context'] )
							 && $record['status'] === 'failed'
							 && $record['context'] === 'regular';
			}
		);

		if ( empty( $live ) ) {
			return;
		}

		$settings_url  = wp_mail_smtp()->get_admin()->get_admin_page_url();
		$connection_id = array_keys( $live )[0];

		if ( count( $live ) === 1 && $connection_id !== 'primary' ) {
			$settings_url = add_query_arg(
				[
					'page'          => 'wp-mail-smtp',
					'tab'           => 'connections',
					'mode'          => 'edit',
					'connection_id' => $connection_id,
				],
				admin_url( 'admin.php' )
			);
		}

		printf(
			'<div class="notice notice-error"><p>%1$s <a href="%2$s">%3$s</a></p></div>',
			esc_html__( 'WP Mail SMTP: One or more emails recently failed to send.', 'wp-mail-smtp' ),
			esc_url( $settings_url ),
			esc_html__( 'View details', 'wp-mail-smtp' )
		);
	}

	/**
	 * AJAX endpoint: dismiss a single connection's failure record.
	 *
	 * @since 4.9.0
	 */
	public function ajax_dismiss() {

		if ( ! check_ajax_referer( 'wp-mail-smtp-admin', 'nonce', false ) ) {
			wp_send_json_error( esc_html__( 'Security check failed. Please reload the page and try again.', 'wp-mail-smtp' ) );
		}

		if ( ! current_user_can( wp_mail_smtp()->get_capability_manage_options() ) ) {
			wp_send_json_error( esc_html__( 'You don\'t have permission to perform this action.', 'wp-mail-smtp' ) );
		}

		$connection_id = isset( $_POST['connection_id'] ) ? sanitize_key( wp_unslash( $_POST['connection_id'] ) ) : '';

		if ( empty( $connection_id ) ) {
			wp_send_json_error( esc_html__( 'Missing connection identifier.', 'wp-mail-smtp' ) );
		}

		EmailSendingDebug::clear( $connection_id );

		wp_send_json_success();
	}

	/**
	 * Resolve the connection_id when viewing an Additional Connection's edit page.
	 *
	 * Returns an empty string when not on the AC edit view. Read-only access to
	 * request superglobals — no state changes are made here.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	private function get_primary_banner_connection_id() {

		$connection_id = 'primary';

		// Read-only banner-target resolution during admin render. Submission
		// nonce is verified by TestTab::process_post() before any side effects,
		// and the AC edit view's connection_id is a navigation parameter (not
		// processed input).
		// phpcs:disable WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended
		if ( $this->is_connections_tab() && isset( $_GET['connection_id'] ) ) {
			$connection_id = sanitize_key( wp_unslash( $_GET['connection_id'] ) );
		} elseif ( $this->is_test_tab() && isset( $_POST['wp-mail-smtp']['test']['connection'] ) ) {
			$connection_id = sanitize_key( wp_unslash( $_POST['wp-mail-smtp']['test']['connection'] ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended

		return $connection_id;
	}

	/**
	 * Look up a connection's display name (primary or additional).
	 *
	 * @since 4.9.0
	 *
	 * @param string $connection_id Connection ID.
	 *
	 * @return string
	 */
	private function get_connection_name( $connection_id ) {

		if ( $connection_id === 'primary' ) {
			return esc_html__( 'Primary Connection', 'wp-mail-smtp' );
		}

		$connection = wp_mail_smtp()->get_connections_manager()->get_connection( $connection_id, false );

		if ( $connection ) {
			return $connection->get_name();
		}

		return esc_html__( 'an additional connection', 'wp-mail-smtp' );
	}

	/**
	 * Render the full banner for a connection (primary scope, or General Settings
	 * banner shell).
	 *
	 * @since 4.9.0
	 *
	 * @param string $role            Role: 'primary' or 'additional'.
	 * @param array  $record          Failure record.
	 * @param string $connection_name Display name.
	 * @param string $connection_id   Connection id this banner is rendering ('primary' or an additional id).
	 */
	private function print_primary_banner( $role, $record, $connection_name, $connection_id ) {

		$is_warning = ( isset( $record['status'] ) && $record['status'] === 'sent' );

		$severity_class = $is_warning
			? 'wpms-email-sending-errors-banner--warning-severity'
			: 'wpms-email-sending-errors-banner--failed-severity';

		$severity_label = $is_warning
			? esc_html__( 'Warning', 'wp-mail-smtp' )
			: esc_html__( 'Error', 'wp-mail-smtp' );

		$title = $this->get_title( $record, $role, $connection_name );

		$doc_state = $this->resolve_doc_state( $record );

		ob_start();
		$this->print_banner_body( $record, $doc_state, $connection_id );
		$body = ob_get_clean();

		printf(
			'<div class="wpms-email-sending-errors-banner %1$s" data-connection-id="%2$s">' .
			'<button type="button" class="notice-dismiss wpms-email-sending-errors-banner__dismiss" aria-label="%3$s"><span class="screen-reader-text">%3$s</span></button>' .
			'<div class="wpms-email-sending-errors-banner__header">' .
			'<span class="wpms-email-sending-errors-banner__icon wpms:icon-[fa6-solid--circle-exclamation] wpms:w-[16px] wpms:h-[16px] wpms:shrink-0" role="img" aria-label="%4$s"></span>' .
			'<p class="wpms-email-sending-errors-banner__title">%5$s</p>' .
			'</div>' .
			'<div class="wpms-email-sending-errors-banner__body">%6$s</div>' .
			'</div>',
			esc_attr( $severity_class ),
			esc_attr( $connection_id ),
			esc_attr__( 'Dismiss this notice', 'wp-mail-smtp' ),
			esc_attr( $severity_label ),
			$title, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$body // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	/**
	 * Build the banner title.
	 *
	 * The generic "last email was unsuccessful" string is the unconditional
	 * fallback; specific framings override for primary-rescued, additional-failed,
	 * and additional-rescued. The test-context variants substitute "test email"
	 * (bolded) for "email".
	 *
	 * @since 4.9.0
	 *
	 * @param array  $record          Failure record.
	 * @param string $role            Role: 'primary' or 'additional'.
	 * @param string $connection_name Display name for the failing connection.
	 *
	 * @return string Title HTML (with bolded interpolations).
	 */
	private function get_title( $record, $role, $connection_name ) {

		$is_test    = ( isset( $record['context'] ) && $record['context'] === 'test' );
		$rescued    = ( isset( $record['status'] ) && $record['status'] === 'sent' );
		$is_primary = ( $role === 'primary' );
		$name_html  = '<strong>' . esc_html( $connection_name ) . '</strong>';

		if ( $is_primary && $rescued ) {
			return $is_test
				? esc_html__( 'Heads up! Your primary connection failed to send the last test email. Your backup connection sent it successfully', 'wp-mail-smtp' )
				: esc_html__( 'Heads up! Your primary connection failed to send the last email. Your backup connection sent it successfully', 'wp-mail-smtp' );
		}

		if ( ! $is_primary && $rescued ) {
			return $is_test
				? sprintf( /* translators: %s: the connection name, bolded. */
					__( 'Heads up! %s failed to send the last test email. Your backup connection sent it successfully', 'wp-mail-smtp' ),
					$name_html
				)
				: sprintf( /* translators: %s: the connection name, bolded. */
					__( 'Heads up! %s failed to send the last email. Your backup connection sent it successfully', 'wp-mail-smtp' ),
					$name_html
				);
		}

		if ( ! $is_primary && ! $rescued ) {
			return $is_test
				? sprintf( /* translators: %s: connection name, bolded. */
					__( 'Heads up! The last test email your site attempted to send via %s was unsuccessful', 'wp-mail-smtp' ),
					$name_html
				)
				: sprintf( /* translators: %s: the additional connection name, bolded. */
					__( 'Heads up! The last email your site attempted to send via %s was unsuccessful', 'wp-mail-smtp' ),
					$name_html
				);
		}

		if ( $is_test ) {
			return esc_html__( 'Heads up! The last test email your site attempted to send was unsuccessful', 'wp-mail-smtp' );
		}

		return esc_html__( 'Heads up! The last email your site attempted to send was unsuccessful', 'wp-mail-smtp' );
	}

	/**
	 * Classify the banner body branch from the registry lookup.
	 *
	 * Returns one of three states:
	 * - `doc_present`     — Registry has a doc URL for the (mailer, error_key) pair.
	 * - `doc_not_present` — Registry has no doc URL (mailer/error_key recognized but not yet documented).
	 * - `unmatched`       — No structured error_key recorded.
	 *
	 * @since 4.9.0
	 *
	 * @param array $record Failure record.
	 *
	 * @return array Shape: [ 'state' => string, 'doc_url' => string|null ]
	 */
	private function resolve_doc_state( $record ) {

		$mailer     = isset( $record['mailer'] ) ? (string) $record['mailer'] : '';
		$error_key  = isset( $record['error_key'] ) ? (string) $record['error_key'] : '';

		if ( $error_key === '' ) {
			return [
				'state'   => 'unmatched',
				'doc_url' => null,
			];
		}

		$url = $this->registry->doc_url_for( $mailer, $error_key );

		if ( $url === null ) {
			return [
				'state'   => 'doc_not_present',
				'doc_url' => null,
			];
		}

		return [
			'state'   => 'doc_present',
			'doc_url' => $url,
		];
	}

	/**
	 * Print the body branch for the full banner.
	 *
	 * Three branches:
	 *  - doc_present     — known-issue copy + "View Troubleshoot Guide" CTA.
	 *  - doc_not_present — fallback copy + "Send Test Email" CTA (Lite) /
	 *                      "Need hands-on help?" upsell (Pro).
	 *  - unmatched       — inline diagnostic from $record['error_message'].
	 *
	 * @since 4.9.0
	 *
	 * @param array  $record        Failure record.
	 * @param array  $doc_state     Output of {@see resolve_doc_state()}.
	 * @param string $connection_id Connection id this banner belongs to. When not
	 *                              'primary', the "Send Test Email" CTA carries
	 *                              `connection_id=<id>` so the Test tab pre-selects
	 *                              that connection.
	 */
	private function print_banner_body( $record, $doc_state, $connection_id ) {

		$test_tab_url_args = [
			'tab'        => 'test',
			'auto-start' => 1,
		];

		if ( $connection_id !== 'primary' ) {
			$test_tab_url_args['connection_id'] = $connection_id;
		}

		$test_tab_url = add_query_arg(
			$test_tab_url_args,
			wp_mail_smtp()->get_admin()->get_admin_page_url( 'wp-mail-smtp-tools' )
		);

		switch ( $doc_state['state'] ) {
			case 'doc_present':
				printf(
					'<p>%s</p>',
					esc_html__( "We've identified your error as a known issue and have a clear, step-by-step solution ready for you! Just refer our troubleshoot guide to get started.", 'wp-mail-smtp' )
				);

				$actions = [
					sprintf(
						'<a href="%1$s" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-orange" target="_blank" rel="noopener noreferrer">%2$s</a>',
						esc_url( wp_mail_smtp()->get_utm_url( $doc_state['doc_url'], [ 'medium' => 'email-sending-errors-banner', 'content' => 'Troubleshoot Guide' ] ) ), // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
						esc_html__( 'Open Troubleshoot Guide', 'wp-mail-smtp' )
					),
					$this->build_error_log_toggle_button( $record ),
					sprintf(
						'<a href="%1$s" class="wp-mail-smtp-btn wp-mail-smtp-btn-link">%2$s</a>',
						esc_url( $test_tab_url ),
						esc_html__( 'Send Test Email', 'wp-mail-smtp' )
					),
				];

				$this->print_actions_row( $actions );
				$this->print_error_log_panel( $record );
				$this->print_need_help( $record, $doc_state );
				break;

			case 'doc_not_present':
				$this->print_failure_cause( $record );

				$actions = [
					$this->build_error_log_toggle_button( $record ),
					sprintf(
						'<a href="%1$s" class="wp-mail-smtp-btn wp-mail-smtp-btn-link">%2$s</a>',
						esc_url( $test_tab_url ),
						esc_html__( 'Send Test Email', 'wp-mail-smtp' )
					),
				];

				$this->print_actions_row( $actions );
				$this->print_error_log_panel( $record );
				$this->print_need_help( $record, $doc_state );
				break;

			case 'unmatched':
			default:
				$diagnostic = isset( $record['error_message'] ) ? (string) $record['error_message'] : '';

				printf(
					'<p>%1$s</p>',
					esc_html__( 'No structured error code was recorded for this failure. The diagnostic from the mailer is below.', 'wp-mail-smtp' )
				);

				if ( $diagnostic !== '' ) {
					printf(
						'<pre class="wpms-email-sending-errors-banner__diagnostic">%s</pre>',
						esc_html( $diagnostic )
					);
				}
				break;
		}
	}

	/**
	 * Render the actions row inside the banner body.
	 *
	 * Wraps the supplied markup chunks in a flex container so they sit on one
	 * row per the design. Empty strings are skipped so callers can pass
	 * conditional actions without extra branching.
	 *
	 * @since 4.9.0
	 *
	 * @param string[] $actions Pre-rendered action markup (buttons, links).
	 */
	private function print_actions_row( $actions ) {

		$actions = array_filter( $actions );

		if ( empty( $actions ) ) {
			return;
		}

		$allowed_tags = [
			'a'      => [
				'href'   => [],
				'rel'    => [],
				'target' => [],
				'class'  => [],
			],
			'button' => [
				'type'            => [],
				'class'           => [],
				'data-show-label' => [],
				'data-hide-label' => [],
			],
			'span'   => [
				'class' => [],
			],
		];

		echo '<div class="wpms-email-sending-errors-banner__actions">';
		foreach ( $actions as $action ) {
			echo wp_kses( $action, $allowed_tags );
		}
		echo '</div>';
	}

	/**
	 * Build the "View Error Log" toggle-button markup for inclusion in the
	 * banner's actions row. Returns an empty string when the record has no
	 * error message to display.
	 *
	 * @since 4.9.0
	 *
	 * @param array $record Failure record.
	 *
	 * @return string
	 */
	private function build_error_log_toggle_button( $record ) {

		if ( empty( $record['error_message'] ) ) {
			return '';
		}

		return sprintf(
			'<button type="button" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-light-grey wpms-email-sending-errors-banner__error-log-toggle" data-show-label="%1$s" data-hide-label="%2$s">%1$s</button>',
			esc_attr__( 'View Error Log', 'wp-mail-smtp' ),
			esc_attr__( 'Hide Error Log', 'wp-mail-smtp' )
		);
	}

	/**
	 * Render the inline collapsible error-log panel (initially hidden).
	 *
	 * Paired with the toggle button produced by
	 * {@see build_error_log_toggle_button()}.
	 *
	 * @since 4.9.0
	 *
	 * @param array $record Failure record.
	 */
	private function print_error_log_panel( $record ) {

		if ( empty( $record['error_message'] ) ) {
			return;
		}

		$allowed_tags = [
			'a'      => [
				'href'   => [],
				'rel'    => [],
				'target' => [],
			],
			'p'      => [],
			'strong' => [],
			'b'      => [],
			'i'      => [],
			'br'     => [],
			'code'   => [],
			'ul'     => [],
			'ol'     => [],
			'li'     => [],
			'pre'    => [],
		];

		printf(
			'<div class="wpms-email-sending-errors-banner__error-log wpms-email-sending-errors-error-log" hidden>' .
			'<div class="wpms-email-sending-errors-error-log__inner">' .
			'<button type="button" class="wpms-email-sending-errors-error-log__copy-icon" aria-label="%1$s" title="%1$s">' .
			'<span class="wpms-email-sending-errors-error-log__copy-icon-default wpms:icon-[fa6-regular--copy] wpms:w-[16px] wpms:h-[16px]"></span>' .
			'<span class="wpms-email-sending-errors-error-log__copy-icon-done wpms:icon-[fa6-solid--check] wpms:text-success wpms:w-[16px] wpms:h-[16px]" hidden></span>' .
			'</button>' .
			'<span class="wpms-email-sending-errors-error-log__copy-tooltip" hidden>%2$s</span>' .
			'%3$s' .
			'</div>' .
			'</div>',
			esc_attr__( 'Copy error log', 'wp-mail-smtp' ),
			esc_html__( 'Copied!', 'wp-mail-smtp' ),
			wp_kses( $this->build_error_log( $record ), $allowed_tags )
		);
	}

	/**
	 * Render the full error log from a stored failure record.
	 *
	 * Reads the snapshot data captured at write-time (see
	 * {@see \WPMailSMTP\MailCatcherTrait::record_send_failure()}) instead of consulting
	 * live request state, so the bundle reflects the moment the failure occurred even
	 * if the admin views it later.
	 *
	 * @since 1.0.0
	 *
	 * @param array $record EmailSendingDebug record for a single connection.
	 *
	 * @return string Pre-formatted HTML block.
	 */
	private function build_error_log( $record ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded -- Linear assembly of optional debug sections; splitting would scatter the bundle layout.

		$mailer_slug = isset( $record['mailer'] ) ? $record['mailer'] : '';
		$is_smtp     = ( $mailer_slug === 'smtp' );

		/*
		 * Versions Debug.
		 */
		$versions_text  = '<strong>Versions:</strong><br>';
		$versions_text .= '<strong>WordPress:</strong> ' . esc_html( isset( $record['wp_version'] ) ? $record['wp_version'] : '' ) . '<br>';
		$versions_text .= '<strong>WordPress MS:</strong> ' . ( ! empty( $record['is_multisite'] ) ? 'Yes' : 'No' ) . '<br>';
		$versions_text .= '<strong>PHP:</strong> ' . esc_html( isset( $record['php_version'] ) ? $record['php_version'] : '' ) . '<br>';
		$versions_text .= '<strong>WP Mail SMTP:</strong> ' . esc_html( isset( $record['plugin_version'] ) ? $record['plugin_version'] : '' ) . '<br>';

		/*
		 * Mailer Debug.
		 */
		$mailer_text  = '<strong>Params:</strong><br>';
		$mailer_text .= '<strong>Mailer:</strong> ' . esc_html( $mailer_slug ) . '<br>';

		if ( $record['context'] !== 'test' ) {
			$mailer_text .= '<strong>Source:</strong> ' . esc_html( isset( $record['initiator_name'] ) ? $record['initiator_name'] : '' ) . ' - ' . esc_html( isset( $record['initiator_file'] ) ? $record['initiator_file'] : '' ) . '<br>';
		}

		$mailer_text .= '<strong>Constants:</strong> ' . ( ! empty( $record['constants_enabled'] ) ? 'Yes' : 'No' ) . '<br>';

		if ( ! empty( $record['conflicts'] ) ) {
			$mailer_text .= '<strong>Conflicts:</strong> ' . esc_html( implode( ', ', (array) $record['conflicts'] ) ) . '<br>';
		}

		if ( ! empty( $record['mailer_debug_info'] ) ) {
			$mailer_text .= $record['mailer_debug_info'];
		}

		if ( ! empty( $record['error_message'] ) ) {
			$mailer_text .= '<br><br><strong>Error:</strong><br>' .
											wp_strip_all_tags( $record['error_message'] ) .
											'<br>';
		}

		/*
		 * SMTP Debug.
		 */
		$smtp_text = '';

		if ( $is_smtp ) {
			$smtp_text  = '<strong>SMTP Debug:</strong><br>';
			$smtp_text .= ! empty( $record['smtp_debug_info'] )
				? '<pre>' . $record['smtp_debug_info'] . '</pre>'
				: '[empty]';
		}

		/**
		 * Filters the sections rendered in the failure-record error log block.
		 *
		 * Each section is an already-formatted HTML string; the final block is
		 * `<pre>` + sections joined by `<br>` (empty sections dropped).
		 *
		 * @since 1.0.0
		 *
		 * @param string[] $sections {
		 *                           Indexed array of section HTML strings.
		 *
		 * @type string $0 Versions section.
		 * @type string $1 Mailer params + provider debug + (non-SMTP) error.
		 * @type string $2 Debug section. Reserved for backward-compat with the
		 *                           historical signature; no longer populated here.
		 * @type string $3 SMTP debug section (empty for non-SMTP mailers).
		 *                           }
		 */
		$errors = apply_filters( // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName,WPForms.Comments.ParamTagHooks.InvalidParamTagsQuantity -- Public hook name + 1-arg signature preserved for backward compatibility after relocation from TestTab.
			'wp_mail_smtp_admin_test_get_debug_messages',
			[
				$versions_text,
				$mailer_text,
				// Reserved slot to preserve the historical filter signature.
				'',
				$smtp_text,
			]
		);

		return '<pre>' . implode( '<br>', array_filter( $errors ) ) . '</pre>';
	}

	/**
	 * Render the `description` block from `get_local_failure_info()` as the cause
	 * statement at the top of a Doc Not Present banner body.
	 *
	 * @since 4.9.0
	 *
	 * @param array $record Failure record.
	 */
	private function print_failure_cause( $record ) {

		$info        = $this->get_local_failure_info( $record );
		$description = isset( $info['description'] ) && is_array( $info['description'] ) ? $info['description'] : [];

		if ( empty( $description ) ) {
			return;
		}

		$allowed_tags = [
			'a'      => [
				'href'   => [],
				'rel'    => [],
				'target' => [],
			],
			'strong' => [],
			'b'      => [],
			'em'     => [],
			'i'      => [],
			'code'   => [],
		];

		echo '<div class="wpms-email-sending-errors-banner__failure-cause">';
		printf(
			'<p><strong>%s</strong></p>',
			esc_html__( 'Typically this error is returned for one of the following reasons:', 'wp-mail-smtp' )
		);

		echo '<ul>';
		foreach ( $description as $cause ) {
			echo '<li>' . wp_kses( $cause, $allowed_tags ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</ul>';
		echo '</div>';
	}

	/**
	 * Returns debug information for detection, processing, and display.
	 *
	 * Pattern-matches the failure record's error text against a registry of known
	 * issue signatures and returns the matched (title, description, steps) bundle,
	 * or a generic default when nothing matches.
	 *
	 * @since 4.9.0
	 *
	 * @param array $record EmailSendingDebug record for a single connection.
	 *
	 * @return array
	 */
	private function get_local_failure_info( $record ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded,Generic.Metrics.NestingLevel.MaxExceeded -- Pattern-matching switch over a static registry of failure signatures; flattening would scatter the registry.

		$details = [
			// [any] - cURL error 60/77.
			[
				'mailer'      => 'any',
				'errors'      => [
					[ 'cURL error 60' ],
					[ 'cURL error 77' ],
				],
				'title'       => esc_html__( 'SSL certificate issue.', 'wp-mail-smtp' ),
				'description' => [
					esc_html__( 'Your server cannot establish secure HTTPS connections.', 'wp-mail-smtp' ),
					esc_html__( 'Your server\'s CA certificate bundle is outdated.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Contact your web hosting provider and inform them your site has an issue with SSL certificates.', 'wp-mail-smtp' ),
					esc_html__( 'Share the Error Log below with them so they can resolve the issue.', 'wp-mail-smtp' ),
				],
			],
			// [any] - cURL error 6/7.
			[
				'mailer'      => 'any',
				'errors'      => [
					[ 'cURL error 6' ],
					[ 'cURL error 7' ],
				],
				'title'       => esc_html__( 'Could not connect to host.', 'wp-mail-smtp' ),
				'description' => [
					esc_html__( 'Your web server is blocking outbound connections.', 'wp-mail-smtp' ),
					esc_html__( 'Your SMTP host is rejecting the connection.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Contact your web hosting provider and ask them to verify your server can make outbound connections, and whether a firewall or security policy may be blocking them.', 'wp-mail-smtp' ),
					esc_html__( 'If using the "Other SMTP" Mailer, triple-check your SMTP settings (host, email, password) and confirm with your SMTP host that they accept outside connections using those settings.', 'wp-mail-smtp' ),
				],
			],
			// [sendgrid] - cURL error 18 - potential incorrect API key.
			[
				'mailer'      => 'sendgrid',
				'errors'      => [
					[ 'cURL error 18' ],
				],
				'title'       => esc_html__( 'Invalid SendGrid API key.', 'wp-mail-smtp' ),
				'description' => [
					esc_html__( 'Your SendGrid API key is invalid or has been revoked.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Go to the WP Mail SMTP plugin Settings page.', 'wp-mail-smtp' ),
					esc_html__( 'Make sure your API Key in the SendGrid mailer settings is correct and valid.', 'wp-mail-smtp' ),
					esc_html__( 'Save the plugin settings.', 'wp-mail-smtp' ),
					esc_html__( 'If updating the API Key doesn\'t resolve this issue, please contact our support.', 'wp-mail-smtp' ),
				],
			],
			// [any] - cURL error XX (other).
			[
				'mailer'      => 'any',
				'errors'      => [
					[ 'cURL error' ],
				],
				'title'       => esc_html__( 'Could not connect to your host.', 'wp-mail-smtp' ),
				'description' => [
					esc_html__( 'Your server is unable to make outbound HTTP connections.', 'wp-mail-smtp' ),
					esc_html__( 'Your hosting provider is blocking the request.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Contact your web hosting provider and inform them you are having issues making outbound connections.', 'wp-mail-smtp' ),
					esc_html__( 'Share the Error Log below with them so they can resolve the issue.', 'wp-mail-smtp' ),
				],
			],
			// [smtp] - SMTP Error: Count not authenticate.
			[
				'mailer'      => 'smtp',
				'errors'      => [
					[ 'SMTP Error: Could not authenticate.' ],
				],
				'title'       => esc_html__( 'Could not authenticate your SMTP account.', 'wp-mail-smtp' ),
				'description' => [
					esc_html__( 'Your SMTP username or password is incorrect.', 'wp-mail-smtp' ),
					esc_html__( 'Your SMTP host requires a different authentication method.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Triple-check your SMTP settings including host address, email, and password. If you have recently reset your password you will need to update the settings.', 'wp-mail-smtp' ),
					esc_html__( 'Contact your SMTP host to confirm you are using the correct username and password.', 'wp-mail-smtp' ),
					esc_html__( 'Verify with your SMTP host that your account has permissions to send emails using outside connections.', 'wp-mail-smtp' ),
					sprintf(
						wp_kses( /* translators: %s - URL to the wpmailsmtp.com doc page. */
							__( 'Visit <a href="%s" target="_blank" rel="noopener noreferrer">our documentation</a> for additional tips on how to resolve this error.', 'wp-mail-smtp' ),
							[
								'a' => [
									'href'   => [],
									'target' => [],
									'rel'    => [],
								],
							]
						),
						esc_url( wp_mail_smtp()->get_utm_url( 'https://wpmailsmtp.com/docs/how-to-set-up-the-other-smtp-mailer-in-wp-mail-smtp/#auth-type', [ 'medium' => 'email-test', 'content' => 'Other SMTP auth debug - our documentation' ] ) ) // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
					),
				],
			],
			// [smtp] - Sending bulk email, hitting rate limit.
			[
				'mailer'      => 'smtp',
				'errors'      => [
					[ 'We do not authorize the use of this system to transport unsolicited' ],
				],
				'title'       => esc_html__( 'Error due to unsolicited and/or bulk e-mail.', 'wp-mail-smtp' ),
				'description' => [
					esc_html__( 'You are sending more emails than your SMTP host allows.', 'wp-mail-smtp' ),
					esc_html__( 'Your message was flagged as spam by the SMTP host.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Reduce the recipient count per email: keep single emails to under 10 recipients.', 'wp-mail-smtp' ),
					esc_html__( 'Install an email-logging plugin to inspect your TO, CC, and BCC recipients.', 'wp-mail-smtp' ),
					esc_html__( 'Contact your SMTP host to ask about sending and rate limits.', 'wp-mail-smtp' ),
					esc_html__( 'Verify with them that your SMTP account is in good standing and has not been flagged.', 'wp-mail-smtp' ),
				],
			],
			// [smtp] - Unauthenticated senders not allowed.
			[
				'mailer'      => 'smtp',
				'errors'      => [
					[ 'Unauthenticated senders not allowed' ],
				],
				'title'       => esc_html__( 'Unauthenticated senders are not allowed.', 'wp-mail-smtp' ),
				'description' => [
					esc_html__( 'Your SMTP host requires authentication, but it is disabled in the plugin settings.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Go to the WP Mail SMTP plugin Settings page.', 'wp-mail-smtp' ),
					esc_html__( 'Enable Authentication.', 'wp-mail-smtp' ),
					esc_html__( 'Enter the correct SMTP Username (usually an email address) and Password in the appropriate fields.', 'wp-mail-smtp' ),
				],
			],
			// [smtp] - certificate verify failed.
			// Has to be defined before "SMTP connect() failed" error, since this is a more specific error,
			// which contains the "SMTP connect() failed" error message as well.
			[
				'mailer'      => 'smtp',
				'errors'      => [
					[ 'certificate verify failed' ],
				],
				'title'       => esc_html__( 'Misconfigured server certificate.', 'wp-mail-smtp' ),
				'description' => [
					esc_html__( 'The SMTP host\'s SSL certificate is misconfigured or expired.', 'wp-mail-smtp' ),
					esc_html__( 'Your server\'s OpenSSL CA bundle is outdated.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Verify that the host\'s SSL certificate is valid.', 'wp-mail-smtp' ),
					sprintf(
						wp_kses( /* translators: %s - URL to the PHP openssl manual. */
							__( 'Contact your hosting support and share the Error Log below along with this <a href="%s" target="_blank" rel="noopener noreferrer">link</a>.', 'wp-mail-smtp' ),
							[
								'a' => [
									'href'   => [],
									'target' => [],
									'rel'    => [],
								],
							]
						),
						'https://www.php.net/manual/en/migration56.openssl.php'
					),
				],
			],
			// [smtp] - SMTP connect() failed.
			[
				'mailer'      => 'smtp',
				'errors'      => [
					[ 'SMTP connect() failed' ],
				],
				'title'       => esc_html__( 'Could not connect to the SMTP host.', 'wp-mail-smtp' ),
				'description' => [
					esc_html__( 'Your SMTP settings are incorrect (wrong port, security setting, or host).', 'wp-mail-smtp' ),
					esc_html__( 'Your web server is blocking the outbound connection.', 'wp-mail-smtp' ),
					esc_html__( 'Your SMTP host is rejecting the connection.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Triple-check your SMTP settings: host, email, password, port, and security.', 'wp-mail-smtp' ),
					wp_kses(
						__( 'Contact your web hosting provider and ask them to verify your server can connect to your SMTP host on the configured port and encryption, and whether a firewall or security policy may be blocking the connection. Many shared hosts block certain ports. <strong>Note: this is the most common cause of this issue.</strong>', 'wp-mail-smtp' ),
						[
							'strong' => [],
						]
					),
					esc_html__( 'Contact your SMTP host to confirm you are using the correct username and password.', 'wp-mail-smtp' ),
					esc_html__( 'Verify with your SMTP host that your account has permissions to send emails using outside connections.', 'wp-mail-smtp' ),
				],
			],
			// [mailgun] - Please activate your Mailgun account.
			[
				'mailer'      => 'mailgun',
				'errors'      => [
					[ 'Please activate your Mailgun account' ],
				],
				'title'       => esc_html__( 'Mailgun failed.', 'wp-mail-smtp' ),
				'description' => [
					esc_html__( 'Your Mailgun account has not been activated.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Check the inbox you used to create your Mailgun account and click the activation link in the email from Mailgun.', 'wp-mail-smtp' ),
					esc_html__( 'If you do not see the activation email, go to your Mailgun control panel and resend it.', 'wp-mail-smtp' ),
				],
			],
			// [mailgun] - Forbidden.
			[
				'mailer'      => 'mailgun',
				'errors'      => [
					[ 'Forbidden' ],
				],
				'title'       => esc_html__( 'Mailgun failed.', 'wp-mail-smtp' ),
				'description' => [
					esc_html__( 'Your Mailgun API key is incorrect.', 'wp-mail-smtp' ),
					esc_html__( 'Your Mailgun domain name is incorrect.', 'wp-mail-smtp' ),
					esc_html__( 'Your Mailgun region is incorrect.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					sprintf(
						wp_kses( /* translators: %1$s - Mailgun API Key area URL. */
							__( 'Go to your Mailgun account and verify that your <a href="%1$s" target="_blank" rel="noopener noreferrer">Mailgun API Key</a> is correct.', 'wp-mail-smtp' ),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						'https://app.mailgun.com/settings/api_security'
					),
					sprintf(
						wp_kses( /* translators: %1$s - Mailgun domains area URL. */
							__( 'Verify your <a href="%1$s" target="_blank" rel="noopener noreferrer">Domain Name</a> is correct.', 'wp-mail-smtp' ),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						'https://app.mailgun.com/mg/sending/domains'
					),
					esc_html__( 'Verify your domain Region is correct.', 'wp-mail-smtp' ),
				],
			],
			// [mailgun] - Free accounts are for test purposes only.
			[
				'mailer'      => 'mailgun',
				'errors'      => [
					[ 'Free accounts are for test purposes only' ],
				],
				'title'       => esc_html__( 'Mailgun failed.', 'wp-mail-smtp' ),
				'description' => [
					esc_html__( 'Your Mailgun domain has not been verified.', 'wp-mail-smtp' ),
					esc_html__( 'Your Mailgun account is still on the free trial plan.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					sprintf(
						wp_kses( /* translators: %s - Mailgun documentation URL. */
							__( 'Go to our how-to guide for setting up <a href="%s" target="_blank" rel="noopener noreferrer">Mailgun with WP Mail SMTP</a>.', 'wp-mail-smtp' ),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						esc_url( wp_mail_smtp()->get_utm_url( 'https://wpmailsmtp.com/docs/how-to-set-up-the-mailgun-mailer-in-wp-mail-smtp/', [ 'medium' => 'email-test', 'content' => 'Mailgun with WP Mail SMTP' ] ) ) // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
					),
					esc_html__( 'Complete the steps in section "2. Verify Your Domain".', 'wp-mail-smtp' ),
				],
			],
			// [gmail] - 401: Login Required.
			[
				'mailer'      => 'gmail',
				'errors'      => [
					[ '401', 'Login Required' ],
				],
				'title'       => esc_html__( 'Google API Error.', 'wp-mail-smtp' ),
				'description' => [
					esc_html__( 'Gmail authorization has not been completed.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Go to the WP Mail SMTP plugin Settings page.', 'wp-mail-smtp' ),
					esc_html__( 'Click the "Allow plugin to send emails using your Google account" button.', 'wp-mail-smtp' ),
					esc_html__( 'On the Google authorization screen, click "Allow" to grant the plugin permission to send emails on your behalf.', 'wp-mail-smtp' ),
				],
			],
			// [gmail] - 400: Recipient address required.
			[
				'mailer'      => 'gmail',
				'errors'      => [
					[ '400', 'Recipient address required' ],
				],
				'title'       => esc_html__( 'Google API Error.', 'wp-mail-smtp' ),
				'description' => [
					esc_html__( 'The recipient email address is empty or invalid.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Check the "Send To" email address and confirm it is valid and not empty.', 'wp-mail-smtp' ),
					sprintf( /* translators: 1 - correct email address example. 2 - incorrect email address example. */
						esc_html__( 'It should look like %1$s. These are invalid: %2$s.', 'wp-mail-smtp' ),
						'<code>info@example.com</code>',
						'<code>info@localhost</code>, <code>info@192.168.1.1</code>'
					),
					esc_html__( 'If you are generating the email yourself, make sure it includes a TO header.', 'wp-mail-smtp' ),
				],
			],
			// [gmail] - Token has been expired or revoked.
			[
				'mailer'      => 'gmail',
				'errors'      => [
					[ 'invalid_grant', 'Token has been expired or revoked' ],
				],
				'title'       => esc_html__( 'Google API Error.', 'wp-mail-smtp' ),
				'description' => [
					esc_html__( 'Your Gmail OAuth refresh token has expired.', 'wp-mail-smtp' ),
					esc_html__( 'Access was revoked from your Google account.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Go to the WP Mail SMTP plugin Settings page and click the "Remove OAuth Connection" button.', 'wp-mail-smtp' ),
					esc_html__( 'Then click the "Allow plugin to send emails using your Google account" button and re-enable access.', 'wp-mail-smtp' ),
				],
			],
			// [gmail] - Code was already redeemed.
			[
				'mailer'      => 'gmail',
				'errors'      => [
					[ 'invalid_grant', 'Code was already redeemed' ],
				],
				'title'       => esc_html__( 'Google API Error.', 'wp-mail-smtp' ),
				'description' => [
					esc_html__( 'The Google authorization code has already been used.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Do not manually clean up the plugin options to retry the "Allow..." step.', 'wp-mail-smtp' ),
					esc_html__( 'Reinstall the plugin with clean plugin data enabled on the Misc page. This will remove all plugin options so you can safely retry.', 'wp-mail-smtp' ),
					esc_html__( 'Make sure there is no aggressive caching on admin pages, or clear the cache between attempts.', 'wp-mail-smtp' ),
				],
			],
			// [gmail] - 400: Mail service not enabled.
			[
				'mailer'      => 'gmail',
				'errors'      => [
					[ '400', 'Mail service not enabled' ],
				],
				'title'       => esc_html__( 'Google API Error.', 'wp-mail-smtp' ),
				'description' => [
					esc_html__( 'Your Google Workspace trial period has expired.', 'wp-mail-smtp' ),
					esc_html__( 'Gmail is not enabled in your Google Workspace account.', 'wp-mail-smtp' ),
					esc_html__( 'The Gmail API is not enabled in your Google Cloud project.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					sprintf(
						wp_kses( /* translators: %s - Google Workspace Admin area URL. */
							__( 'Verify in <a href="%s" target="_blank" rel="noopener noreferrer">Google Workspace Admin</a> that your trial period has not expired.', 'wp-mail-smtp' ),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						'https://admin.google.com'
					),
					sprintf(
						wp_kses( /* translators: %s - Google Workspace Admin area URL. */
							__( 'In the Apps list of <a href="%s" target="_blank" rel="noopener noreferrer">Google Workspace Admin</a>, confirm that Gmail is enabled.', 'wp-mail-smtp' ),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						'https://admin.google.com'
					),
					sprintf(
						wp_kses( /* translators: %s - Google Cloud Console URL. */
							__( 'Enable the Gmail API in your project from <a href="%s" target="_blank" rel="noopener noreferrer">Google Cloud Console</a>.', 'wp-mail-smtp' ),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						'https://console.cloud.google.com/'
					),
				],
			],
			// [gmail] - 403: Project X is not found and cannot be used for API calls.
			[
				'mailer'      => 'gmail',
				'errors'      => [
					[ '403', 'is not found and cannot be used for API calls' ],
				],
				'title'       => esc_html__( 'Google API Error.', 'wp-mail-smtp' ),
				'description' => [
					esc_html__( 'The Google Cloud project linked to your Client ID/Secret no longer exists.', 'wp-mail-smtp' ),
					esc_html__( 'The Gmail API is not enabled on the project.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Make sure the Client ID/Secret in your settings corresponds to a project that has the Gmail API enabled.', 'wp-mail-smtp' ),
					sprintf(
						wp_kses( /* translators: %s - Gmail documentation URL. */
							esc_html__( 'Follow our <a href="%s" target="_blank" rel="noopener noreferrer">Gmail tutorial</a> to ensure your project and credentials are configured correctly.', 'wp-mail-smtp' ),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						esc_url( wp_mail_smtp()->get_utm_url( 'https://wpmailsmtp.com/docs/how-to-set-up-the-gmail-mailer-in-wp-mail-smtp/', [ 'medium' => 'email-test', 'content' => 'Gmail tutorial' ] ) ) // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
					),
				],
			],
			// [gmail] - The OAuth client was disabled.
			[
				'mailer'      => 'gmail',
				'errors'      => [
					[ 'disabled_client', 'The OAuth client was disabled' ],
				],
				'title'       => esc_html__( 'Google API Error.', 'wp-mail-smtp' ),
				'description' => [
					esc_html__( 'Your Google OAuth client has been disabled.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Make sure your Client ID/Secret corresponds to a project that has the Gmail API enabled.', 'wp-mail-smtp' ),
					esc_html__( 'In Google Cloud Console, re-enable the disabled OAuth client, or create a new OAuth client and update the plugin settings with the new Client ID/Secret.', 'wp-mail-smtp' ),
				],
			],
			// [SMTP.com] - The "channel - not found" issue.
			[
				'mailer'      => 'smtpcom',
				'errors'      => [
					[ 'channel - not found' ],
				],
				'title'       => esc_html__( 'SMTP.com API Error.', 'wp-mail-smtp' ),
				'description' => [
					esc_html__( 'Your SMTP.com Sender Name is incorrect.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Please make sure you entered an accurate Sender Name in WP Mail SMTP plugin settings.', 'wp-mail-smtp' ),
				],
			],
			// [gmail] - GuzzleHttp requires cURL, the allow_url_fopen ini setting, or a custom HTTP handler.
			[
				'mailer'      => 'gmail',
				'errors'      => [
					[ 'GuzzleHttp requires cURL, the allow_url_fopen ini setting, or a custom HTTP handler' ],
				],
				'title'       => esc_html__( 'GuzzleHttp requirements.', 'wp-mail-smtp' ),
				'description' => [
					esc_html__( 'The cURL PHP extension is disabled on your server.', 'wp-mail-smtp' ),
					esc_html__( 'The allow_url_fopen PHP setting is disabled on your server.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Edit your php.ini file on your hosting server.', 'wp-mail-smtp' ),
					esc_html__( '(Recommended) Enable the cURL PHP extension by adding extension=curl to php.ini.', 'wp-mail-smtp' ),
					esc_html__( '(Or) Enable allow_url_fopen by adding allow_url_fopen = On to php.ini.', 'wp-mail-smtp' ),
					esc_html__( 'If you cannot edit php.ini yourself, share the Error Log below with your hosting support and ask them to make this change.', 'wp-mail-smtp' ),
				],
			],
			// [sparkpost] - Forbidden.
			[
				'mailer'      => 'sparkpost',
				'errors'      => [
					[ 'Forbidden' ],
				],
				'title'       => esc_html__( 'SparkPost API failed.', 'wp-mail-smtp' ),
				'description' => [
					esc_html__( 'Your SparkPost API key is incorrect.', 'wp-mail-smtp' ),
					esc_html__( 'Your SparkPost API key is missing the "Transmissions: Read/Write" permission.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					sprintf(
						wp_kses( /* translators: %1$s - SparkPost API Keys area URL, %2$s - SparkPost EU API Keys area URL. */
							__( 'Go to your <a href="%1$s" target="_blank" rel="noopener noreferrer">SparkPost account</a> or <a href="%2$s" target="_blank" rel="noopener noreferrer">SparkPost EU account</a> and verify that your API key is correct.', 'wp-mail-smtp' ),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						'https://app.sparkpost.com/account/api-keys',
						'https://app.eu.sparkpost.com/account/api-keys'
					),
					esc_html__( 'Verify that your API key has "Transmissions: Read/Write" permission.', 'wp-mail-smtp' ),
				],
			],
			// [sparkpost] - Unauthorized.
			[
				'mailer'      => 'sparkpost',
				'errors'      => [
					[ 'Unauthorized' ],
				],
				'title'       => esc_html__( 'SparkPost API failed.', 'wp-mail-smtp' ),
				'description' => [
					esc_html__( 'Your SparkPost account region is incorrect.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Verify that your SparkPost account region is selected in WP Mail SMTP settings.', 'wp-mail-smtp' ),
				],
			],
		];

		/**
		 * [any] - PHP 7.4.x and PCRE library issues.
		 *
		 * @see https://wordpress.org/support/topic/cant-send-emails-using-php-7-4/
		 */
		if (
			version_compare( phpversion(), '7.4', '>=' ) &&
			defined( 'PCRE_VERSION' ) &&
			version_compare( PCRE_VERSION, '10.0', '>' ) &&
			version_compare( PCRE_VERSION, '10.32', '<=' )
		) {
			$details[] = [
				'mailer'      => 'any',
				'errors'      => [
					[ 'Invalid address:  (setFrom)' ],
				],
				'title'       => esc_html__( 'PCRE library issue.', 'wp-mail-smtp' ),
				'description' => [
					esc_html__( 'Your server is running PHP 7.4.x with an outdated libpcre2 library (version 10.32 or earlier) that fails email address validation.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Contact your web hosting provider and inform them you are having issues with the libpcre2 library on PHP 7.4. They should be able to resolve this.', 'wp-mail-smtp' ),
					esc_html__( 'As a temporary workaround, you can downgrade to PHP 7.3 on your server.', 'wp-mail-smtp' ),
				],
			];
		}

		$mailer_slug = isset( $record['mailer'] ) ? $record['mailer'] : '';
		$haystack    = implode(
			"\n",
			array_filter(
				[
					isset( $record['error_message'] ) ? $record['error_message'] : '',
					isset( $record['mailer_debug_info'] ) ? $record['mailer_debug_info'] : '',
					isset( $record['smtp_debug_info'] ) ? $record['smtp_debug_info'] : '',
				]
			)
		);

		// Error detection logic.
		foreach ( $details as $data ) {

			// Check for appropriate mailer.
			if ( $data['mailer'] !== 'any' && $mailer_slug !== $data['mailer'] ) {
				continue;
			}

			$match = false;

			// Attempt to detect errors.
			foreach ( $data['errors'] as $error_group ) {
				foreach ( $error_group as $error_message ) {
					$match = strpos( $haystack, $error_message ) !== false;

					if ( ! $match ) {
						break;
					}
				}
				if ( $match ) {
					break;
				}
			}

			if ( $match ) {
				return $data;
			}
		}

		// Return defaults.
		return [
			'title'       => esc_html__( 'An issue was detected.', 'wp-mail-smtp' ),
			'description' => [
				esc_html__( 'Your plugin settings are incorrect (wrong SMTP settings, invalid Mailer configuration, etc.).', 'wp-mail-smtp' ),
				esc_html__( 'Your web server is blocking the connection.', 'wp-mail-smtp' ),
				esc_html__( 'Your host is rejecting the connection.', 'wp-mail-smtp' ),
			],
			'steps'       => [
				esc_html__( 'Triple-check your plugin settings, reconfigure if needed to make sure everything is correct.', 'wp-mail-smtp' ),
				wp_kses(
					__( 'Contact your web hosting provider and ask them to verify your server can make outside connections, and whether a firewall or security policy may be blocking them. Many shared hosts block certain ports. <strong>Note: this is the most common cause of this issue.</strong>', 'wp-mail-smtp' ),
					[
						'strong' => [],
					]
				),
			],
		];
	}

	/**
	 * Render the "Need Help?" sub-section inside the banner body.
	 *
	 * Renders the matched issue's `steps` (or the default bundle's steps when
	 * nothing matched), followed by the SendLayer Quick Connect upsell (Lite
	 * only), the White Glove Setup bullet, and an edition-specific support
	 * line. Steps are suppressed when a troubleshoot doc URL is available,
	 * since the doc covers the same ground.
	 *
	 * @since 4.9.0
	 *
	 * @param array $record    Failure record.
	 * @param array $doc_state Output of {@see resolve_doc_state()}.
	 */
	private function print_need_help( $record, $doc_state ) {

		$bullets = [];

		$allowed_tags = [
			'a'      => [
				'href'   => [],
				'rel'    => [],
				'target' => [],
				'class'  => [],
			],
			'strong' => [],
			'b'      => [],
			'em'     => [],
			'i'      => [],
			'br'     => [],
			'code'   => [],
			'ul'     => [],
			'li'     => [],
			'span'   => [
				'class' => [],
			],
		];

		if ( $doc_state['state'] !== 'doc_present' ) {
			$info  = $this->get_local_failure_info( $record );
			$steps = isset( $info['steps'] ) && is_array( $info['steps'] ) ? $info['steps'] : [];

			foreach ( $steps as $step ) {
				$bullets[] = wp_kses( $step, $allowed_tags );
			}
		}

		$primary_mailer = (string) Options::init()->get( 'mail', 'mailer' );
		$record_mailer  = isset( $record['mailer'] ) ? (string) $record['mailer'] : '';

		if ( $primary_mailer !== 'sendlayer' && $record_mailer !== 'sendlayer' ) {
			$bullets[] = wp_kses(
				__( 'Resolve your email sending issues with <a href="#" class="js-wp-mail-smtp-sendlayer-quick-connect-link wpms:focus:outline-none! wpms:focus:shadow-none!">SendLayer</a>, and send your <strong>first 200 emails for free</strong> with just a few clicks!', 'wp-mail-smtp' ),
				$allowed_tags
			);
		}

		$bullets[] = sprintf(
			wp_kses( /* translators: %s - White Glove Setup URL. */
				__( 'Need hands-on help? Our <a href="%s" target="_blank" rel="noopener noreferrer">White Glove Setup</a> handles everything for you, just share your site details and we’ll do the rest.', 'wp-mail-smtp' ),
				$allowed_tags
			),
			// TODO: Verify that this White Glove Setup URL is correct and up to date before release.
			esc_url( wp_mail_smtp()->get_utm_url( 'https://wpmailsmtp.com/white-glove-setup/', [ 'medium' => 'email-sending-errors-banner', 'content' => 'White Glove Setup' ] ) ) // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
		);

		if ( $doc_state['state'] !== 'doc_present' ) {
			if ( wp_mail_smtp()->is_pro() ) {
				$bullets[] = sprintf(
					wp_kses( /* translators: %s - WPMailSMTP.com support URL. */
						__( 'Still stuck? Please log in to your <a href="%s" target="_blank" rel="noopener noreferrer">WP Mail SMTP account</a> and submit a support ticket with the error log.', 'wp-mail-smtp' ),
						$allowed_tags
					),
					esc_url( wp_mail_smtp()->get_utm_url( 'https://wpmailsmtp.com/account/support/', [ 'medium' => 'email-sending-errors-banner', 'content' => 'submit a support ticket' ] ) ) // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
				);
			} else {
				$bullets[] = sprintf(
					wp_kses( /* translators: %1$s - wordpress.org support forum URL, %2$s - upgrade-to-Pro URL. */
						__( 'Still stuck? Get priority support by <a href="%1$s" target="_blank" rel="noopener noreferrer">Upgrading to Pro</a> or <a href="%2$s" target="_blank" rel="noopener noreferrer">create a support thread</a> along with the error log on WordPress.org forums. ', 'wp-mail-smtp' ),
						$allowed_tags
					),
					esc_url( wp_mail_smtp()->get_upgrade_link( [ 'medium' => 'email-sending-errors-banner', 'content' => 'Upgrading to Pro' ] ) ), // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
					'https://wordpress.org/support/plugin/wp-mail-smtp/',
				);
			}
		}

		echo '<div class="wpms-email-sending-errors-banner__help">';
		printf(
			'<p class="wpms-email-sending-errors-banner__help-title"><span aria-hidden="true" class="wpms:icon-[fa6-solid--circle-question] wpms:text-utility-yellow-50 wpms:w-[14px] wpms:h-[14px] wpms:shrink-0"></span><span>%s</span></p>',
			esc_html__( 'Need Help?', 'wp-mail-smtp' )
		);
		echo '<ul>';
		foreach ( $bullets as $bullet ) {
			echo '<li>' . $bullet . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</ul>';
		echo '</div>';
	}

	/**
	 * Render the verbose one-liner notice for an additional connection. On the
	 * General Settings page these stack vertically under the primary banner.
	 *
	 * @since 4.9.0
	 *
	 * @param array               $record     Failure record.
	 * @param ConnectionInterface $connection Additional connection object.
	 */
	private function print_additional_connection_note( $record, $connection ) {

		$connection_id   = $connection->get_id();
		$connection_name = $connection->get_name();

		if ( $connection_name === '' ) {
			$connection_name = esc_html__( 'an additional connection', 'wp-mail-smtp' );
		}

		$severity_class = ( isset( $record['status'] ) && $record['status'] === 'sent' )
			? 'notice-warning'
			: 'notice-error';

		$edit_url = add_query_arg(
			[
				'page'          => 'wp-mail-smtp',
				'tab'           => 'connections',
				'mode'          => 'edit',
				'connection_id' => $connection_id,
			],
			admin_url( 'admin.php' )
		);

		$is_backup     = $this->is_backup_connection( $connection_id );
		$name_html     = '<strong>' . esc_html( $connection_name ) . '</strong>';
		$rescued       = ( isset( $record['status'] ) && $record['status'] === 'sent' );
		$is_test       = ( isset( $record['context'] ) && $record['context'] === 'test' );
		$test_word     = '<strong>' . esc_html__( 'test', 'wp-mail-smtp' ) . '</strong>';
		$backup_prefix = $is_backup
			? sprintf( /* translators: %s: connection name, bolded. */
				__( 'your backup connection %s', 'wp-mail-smtp' ),
				$name_html
			)
			: $name_html;

		if ( $rescued ) {
			$copy = sprintf( /* translators: %s: connection display name. */
				__( '%s failed to send the last email. Your backup connection sent it successfully.', 'wp-mail-smtp' ),
				$backup_prefix
			);
		} elseif ( $is_test ) {
			$copy = sprintf( /* translators: %1$s: literal word "test", bolded. %2$s: connection display name. */
				__( 'The last %1$s email your site attempted to send via %2$s was unsuccessful.', 'wp-mail-smtp' ),
				$test_word,
				$name_html
			);
		} else {
			$copy = sprintf( /* translators: %s: connection display name. */
				__( 'The last email your site attempted to send via %s was unsuccessful.', 'wp-mail-smtp' ),
				$backup_prefix
			);
		}

		$manage_html = wp_kses(
			sprintf(
				/* translators: %s - link to the Additional Connection Settings page. */
				__( 'Manage it on the %s page.', 'wp-mail-smtp' ),
				sprintf(
					'<a href="%s">%s</a>',
					esc_url( $edit_url ),
					esc_html__( 'Additional Connection Settings', 'wp-mail-smtp' )
				)
			),
			[ 'a' => [ 'href' => [] ] ]
		);

		printf(
			'<div class="notice %1$s is-dismissible wpms-email-sending-errors-one-liner" data-connection-id="%2$s">' .
			'<p>%3$s %4$s</p>' .
			'</div>',
			esc_attr( $severity_class ),
			esc_attr( $connection_id ),
			wp_kses( $copy, [ 'strong' => [] ] ),
			$manage_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	/**
	 * Whether a given additional-connection id is currently configured as the
	 * backup connection. Returns false when Pro is inactive or no backup is set.
	 *
	 * @since 4.9.0
	 *
	 * @param string $connection_id Connection ID.
	 *
	 * @return bool
	 */
	private function is_backup_connection( $connection_id ) {

		$backup_id = Options::init()->get( 'backup_connection', 'connection_id' );

		return ! empty( $backup_id ) && $backup_id === $connection_id;
	}

	/**
	 * Whether we're on Tools > Email Test tab.
	 *
	 * Handles the implicit-default case: {@see \WPMailSMTP\Admin\Pages\Tools::$default_tab}
	 * is `'test'`, so a missing `?tab=…` param on the tools page still means we're
	 * on the test tab. `Area::is_admin_page( 'test' )` can't detect this because
	 * `'test'` isn't a top-level registered page, and `Area::get_current_tab()`
	 * only resolves the tab for the general page.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	private function is_test_tab() {

		if ( ! wp_mail_smtp()->get_admin()->is_admin_page( 'tools' ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

		return $tab === '' || $tab === 'test';
	}

	/**
	 * Whether we're currently viewing the Additional Connections tab on Settings.
	 *
	 * Settings → Connections is `page=wp-mail-smtp&tab=connections`; the AC tab is
	 * NOT a registered WP admin page slug, so `is_admin_page('connections')` falls
	 * back to matching the Settings page (any tab). This helper does the
	 * tab-aware check.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	private function is_connections_tab() {

		if ( ! wp_mail_smtp()->get_admin()->is_admin_page( 'general' ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

		return $tab === 'connections';
	}
}
