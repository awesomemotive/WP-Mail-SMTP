<?php

namespace WPMailSMTP\Admin\Dashboard\Widgets;

use WPMailSMTP\Admin\Area;
use WPMailSMTP\Admin\Dashboard\WidgetState;
use WPMailSMTP\DomainCheckState;
use WPMailSMTP\EmailSendingDebug;
use WPMailSMTP\Options;

/**
 * Connections widget: the site's mail connections and the status of each.
 *
 * @since 4.10.0
 */
class Connections extends AbstractWidget {

	/**
	 * The connection is sending without a recorded problem.
	 *
	 * @since 4.10.0
	 */
	public const STATUS_CONNECTED = 'connected';

	/**
	 * The domain checker found SPF, DKIM or DMARC issues for this connection.
	 *
	 * @since 4.10.0
	 */
	public const STATUS_DOMAIN_WARNING = 'domain_warning';

	/**
	 * The connection has a recorded send failure.
	 *
	 * @since 4.10.0
	 */
	public const STATUS_MAILER_ERROR = 'mailer_error';

	/**
	 * A mailer is selected, but its required settings are not all filled in.
	 *
	 * @since 4.10.0
	 */
	public const STATUS_SETUP_INCOMPLETE = 'setup_incomplete';

	/**
	 * Column placement.
	 *
	 * @since 4.10.0
	 */
	public const COLUMN = 'sidebar';

	/**
	 * Default sort position within the sidebar.
	 *
	 * @since 4.10.0
	 */
	public const ORDER = 20;

	/**
	 * Get the widget identifier.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_id(): string {

		return 'connections';
	}

	/**
	 * Get the widget title.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_title(): string {

		return esc_html__( 'Connections', 'wp-mail-smtp' );
	}

	/**
	 * Get the widget state.
	 *
	 * @since 4.10.0
	 *
	 * @return WidgetState
	 */
	public function get_state(): WidgetState {

		return new WidgetState( true, $this->is_mailer_configured() ? 'data' : 'connect' );
	}

	/**
	 * Status subtext for one connection. A recorded send failure outranks a domain warning.
	 *
	 * @since 4.10.0
	 *
	 * @param string $connection_id Connection id, or 'primary'.
	 *
	 * @return string
	 */
	public function get_status( $connection_id ) {

		if ( $this->has_failure( $connection_id ) ) {
			return self::STATUS_MAILER_ERROR;
		}

		if ( $this->get_domain_state( $connection_id ) === DomainCheckState::ISSUES ) {
			return self::STATUS_DOMAIN_WARNING;
		}

		return self::STATUS_CONNECTED;
	}

	/**
	 * Whether a send failure is recorded against this connection.
	 *
	 * @since 4.10.0
	 *
	 * @param string $connection_id Connection id, or 'primary'.
	 *
	 * @return bool
	 */
	protected function has_failure( $connection_id ) {

		$record = EmailSendingDebug::get( $connection_id );

		return ! empty( $record['status'] ) && $record['status'] === 'failed';
	}

	/**
	 * Recorded domain-check state for this connection.
	 *
	 * @since 4.10.0
	 *
	 * @param string $connection_id Connection id, or 'primary'.
	 *
	 * @return string
	 */
	protected function get_domain_state( $connection_id ) {

		return DomainCheckState::get_state( $connection_id );
	}

	/**
	 * Build one row's data.
	 *
	 * @since 4.10.0
	 *
	 * @param string $connection_id Connection id, or 'primary'.
	 * @param string $cta_label     Row CTA label.
	 *
	 * @return array
	 */
	protected function get_row( $connection_id, $cta_label ) {

		return [
			'connection_id' => $connection_id,
			'mailer_name'   => $this->get_mailer_name(),
			'mailer_slug'   => $this->get_mailer_slug(),
			'status'        => $this->is_mailer_configured()
				? $this->get_status( $connection_id )
				: self::STATUS_SETUP_INCOMPLETE,
			'cta_label'     => $cta_label,
			'cta_url'       => $this->get_settings_url(),
		];
	}

	/**
	 * The current mailer's slug, e.g. 'sendlayer'.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	protected function get_mailer_slug() {

		return (string) Options::init()->get( 'mail', 'mailer' );
	}

	/**
	 * Whether a mailer other than the PHP default is selected, however incomplete its
	 * settings are.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	protected function is_mailer_selected() {

		return ! in_array( $this->get_mailer_slug(), [ '', 'mail' ], true );
	}

	/**
	 * The current mailer's display name.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	protected function get_mailer_name() {

		$options = wp_mail_smtp()->get_providers()->get_options( $this->get_mailer_slug() );

		return $options ? $options->get_title() : '';
	}

	/**
	 * Settings page URL, scrolled to the primary connection's mailer row.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	protected function get_settings_url() {

		return wp_mail_smtp()->get_admin()->get_admin_page_url( Area::SLUG . '#wp-mail-smtp-setting-row-mailer' );
	}

	/**
	 * The backup connection CTA row. Lite has no backup connections, so there is
	 * nothing to add or manage; Pro overrides this with the real, dynamic CTA.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	public function get_backup_cta() {

		return [];
	}

	/**
	 * The three labelled row groups the design shows: Primary, Backup and Additional
	 * Connections. Each group is its own overridable method.
	 *
	 * @since 4.10.0
	 *
	 * @return array Each entry: `[ 'label' => string, 'rows' => array ]`.
	 */
	public function get_groups(): array {

		return [
			$this->get_primary_group(),
			$this->get_backup_group(),
			$this->get_additional_group(),
		];
	}

	/**
	 * The Primary group.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	protected function get_primary_group() {

		return [
			'label' => esc_html__( 'Primary', 'wp-mail-smtp' ),
			'rows'  => [ $this->get_primary_group_row() ],
		];
	}

	/**
	 * The Backup group, rendered locked and pointing at an upgrade on a tier with no
	 * backup connections.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	protected function get_backup_group() {

		return [
			'label' => esc_html__( 'Backup', 'wp-mail-smtp' ),
			'rows'  => [
				$this->get_locked_row(
					esc_html__( 'No backup added', 'wp-mail-smtp' ),
					esc_html__( 'Backup helps when primary fails', 'wp-mail-smtp' ),
					'connections-backup'
				),
			],
		];
	}

	/**
	 * The Additional Connections group, rendered locked and pointing at an upgrade on a
	 * tier that has none.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	protected function get_additional_group() {

		return [
			'label' => esc_html__( 'Additional Connections', 'wp-mail-smtp' ),
			'rows'  => [
				$this->get_locked_row(
					esc_html__( 'New Connection', 'wp-mail-smtp' ),
					esc_html__( 'Setup a new mailer connection', 'wp-mail-smtp' ),
					'connections-additional'
				),
			],
		];
	}

	/**
	 * The Primary group's one row: the real primary connection once one is
	 * configured, or a prompt to connect one.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	protected function get_primary_group_row() {

		if ( ! $this->is_mailer_selected() ) {
			return [
				'title'    => esc_html__( 'No primary connection', 'wp-mail-smtp' ),
				'subtitle' => esc_html__( 'Connect a mailer to start sending emails', 'wp-mail-smtp' ),
				'action'   => [
					'label' => esc_html__( 'Connect', 'wp-mail-smtp' ),
					'url'   => $this->get_settings_url(),
				],
				'locked'   => false,
			];
		}

		return $this->to_group_row( $this->get_row( 'primary', esc_html__( 'Manage', 'wp-mail-smtp' ) ) );
	}

	/**
	 * A locked placeholder row, offering an upgrade instead of the row's usual action.
	 *
	 * @since 4.10.0
	 *
	 * @param string $title           Row title.
	 * @param string $subtitle        Row subtitle.
	 * @param string $upgrade_content The upgrade link's `content` UTM value.
	 *
	 * @return array
	 */
	protected function get_locked_row( $title, $subtitle, $upgrade_content ) {

		return [
			'title'    => $title,
			'subtitle' => $subtitle,
			'action'   => [
				'label' => esc_html__( 'Upgrade', 'wp-mail-smtp' ),
				'url'   => $this->get_upgrade_url( $upgrade_content ),
			],
			'locked'   => true,
		];
	}

	/**
	 * Upgrade link for a locked row.
	 *
	 * @since 4.10.0
	 *
	 * @param string $content The upgrade link's `content` UTM value.
	 *
	 * @return string
	 */
	protected function get_upgrade_url( $content ) {

		return wp_mail_smtp()->get_upgrade_link(
			[
				'medium'  => 'dashboard',
				'content' => $content,
			]
		);
	}

	/**
	 * Convert a connection row (`get_row()` / the Pro `get_connection_row()` shape)
	 * into the row-group template's row shape.
	 *
	 * @since 4.10.0
	 *
	 * @param array $row Connection row: connection_id, mailer_name, mailer_slug, status, cta_label, cta_url.
	 *
	 * @return array Empty when `$row` is empty (e.g. a deleted connection).
	 */
	protected function to_group_row( array $row ) {

		if ( empty( $row ) ) {
			return [];
		}

		// A stub row in a test, or a future caller, may omit a key; default each so
		// the shape below never has to fall back on the fly.
		$row += [
			'mailer_slug' => '',
			'mailer_name' => '',
			'status'      => '',
			'cta_label'   => '',
			'cta_url'     => '',
		];

		return [
			'icon_html' => $this->get_mailer_logo_html( $row['mailer_slug'] ),
			'title'     => $row['mailer_name'],
			'subtitle'  => $this->get_status_label( $row['status'] ),
			'status'    => $row['status'],
			'action'    => [
				'label' => $row['cta_label'],
				'url'   => $row['cta_url'],
			],
			'locked'    => false,
		];
	}

	/**
	 * Display label for a status constant.
	 *
	 * @since 4.10.0
	 *
	 * @param string $status One of the `STATUS_*` constants.
	 *
	 * @return string
	 */
	protected function get_status_label( $status ) {

		$labels = [
			self::STATUS_CONNECTED        => esc_html__( 'Connected', 'wp-mail-smtp' ),
			self::STATUS_DOMAIN_WARNING   => esc_html__( 'Domain check warning', 'wp-mail-smtp' ),
			self::STATUS_MAILER_ERROR     => esc_html__( 'Mailer error', 'wp-mail-smtp' ),
			self::STATUS_SETUP_INCOMPLETE => esc_html__( 'Setup incomplete', 'wp-mail-smtp' ),
		];

		return $labels[ $status ] ?? '';
	}

	/**
	 * A row's mailer logo, decorative since the mailer name is already the row title.
	 * Empty when the slug is unknown or the provider sets no logo.
	 *
	 * @since 4.10.0
	 *
	 * @param string $mailer_slug Mailer slug, e.g. 'sendlayer'.
	 *
	 * @return string
	 */
	protected function get_mailer_logo_html( $mailer_slug ) {

		if ( $mailer_slug === '' ) {
			return '';
		}

		$logo_url = $this->get_mailer_icon_url( $mailer_slug );

		if ( $logo_url === '' ) {
			return '';
		}

		return '<img src="' . esc_url( $logo_url ) . '" alt="">';
	}

	/**
	 * A mailer's compact square icon, the same artwork the setup wizard's mailer list
	 * uses. Falls back to the provider's own logo, which is a wide wordmark and reads
	 * small in a 30px box, for any mailer with no compact icon.
	 *
	 * @since 4.10.0
	 *
	 * @param string $mailer_slug Mailer slug, e.g. 'sendlayer'.
	 *
	 * @return string
	 */
	private function get_mailer_icon_url( $mailer_slug ) {

		$icon = 'images/providers/small/' . $mailer_slug . '.svg';

		if ( file_exists( WPMS_PLUGIN_DIR . 'assets/' . $icon ) ) {
			return wp_mail_smtp()->assets_url . '/' . $icon;
		}

		$options = wp_mail_smtp()->get_providers()->get_options( $mailer_slug );

		return $options ? (string) $options->get_logo_url() : '';
	}

	/**
	 * Email test tool URL.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	protected function get_test_connections_url() {

		return add_query_arg( 'tab', 'test', wp_mail_smtp()->get_admin()->get_admin_page_url( Area::SLUG . '-tools' ) );
	}

	/**
	 * Render the widget body.
	 *
	 * @since 4.10.0
	 *
	 * @param string $variant Template variant.
	 * @param array  $data    Aggregated data.
	 *
	 * @return string
	 */
	protected function render_body( string $variant, array $data ): string {

		$is_mailer_configured = $this->is_mailer_configured();

		return (string) wp_mail_smtp_render(
			'dashboard/widgets/connections',
			[
				'groups'    => $this->get_groups(),
				'cta_label' => $is_mailer_configured
					? esc_html__( 'Test Connections', 'wp-mail-smtp' )
					: esc_html__( 'Setup Primary Connection', 'wp-mail-smtp' ),
				'cta_url'   => $is_mailer_configured ? $this->get_test_connections_url() : $this->get_settings_url(),
			],
			true
		);
	}
}
