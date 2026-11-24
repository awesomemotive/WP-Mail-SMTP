<?php

namespace WPMailSMTP\Admin;

/**
 * Admin Flyout Menu.
 *
 * @since 3.0.0
 */
class FlyoutMenu {

	/**
	 * Hooks.
	 *
	 * @since 3.0.0
	 */
	public function hooks() {

		/**
		 * Filter for enabling/disabling the quick links (flyout menu).
		 *
		 * @since 3.0.0
		 *
		 * @param bool $enabled Whether quick links are enabled.
		 */
		if ( apply_filters( 'wp_mail_smtp_admin_flyout_menu', true ) ) {
			add_action( 'admin_footer', [ $this, 'output' ] );
		}
	}

	/**
	 * Output menu.
	 *
	 * @since 3.0.0
	 */
	public function output() {

		// Bail if we're not on a plugin admin page.
		if ( ! wp_mail_smtp()->get_admin()->is_admin_page() ) {
			return;
		}

		$label = __( 'See Quick Links', 'wp-mail-smtp' );
		?>
		<div id="wp-mail-smtp-flyout">
			<div id="wp-mail-smtp-flyout-items"><?php $this->render_items(); ?></div>
			<a href="#" class="wp-mail-smtp-flyout-button wp-mail-smtp-flyout-head">
				<div class="wp-mail-smtp-flyout-label"><?php echo esc_html( $label ); ?></div>
				<figure><img src="<?php echo esc_url( wp_mail_smtp()->assets_url . '/images/flyout-menu/mascot.svg' ); ?>" alt="<?php echo esc_attr( $label ); ?>"/></figure>
			</a>
		</div>
		<?php
	}

	/**
	 * Print the menu items.
	 *
	 * @since 3.0.0
	 * @since 4.10.5 Prints the items instead of returning their markup.
	 */
	private function render_items() {

		$items = array_reverse( $this->menu_items() );

		foreach ( $items as $item_key => $item ) {
			?>
			<a href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener noreferrer" class="wp-mail-smtp-flyout-button wp-mail-smtp-flyout-item wp-mail-smtp-flyout-item-<?php echo (int) $item_key; ?>"
				<?php echo empty( $item['bgcolor'] ) ? '' : 'style="background-color: ' . esc_attr( $item['bgcolor'] ) . '"'; ?>
				<?php echo empty( $item['hover_bgcolor'] ) ? '' : 'onMouseOver="this.style.backgroundColor=\'' . esc_attr( $item['hover_bgcolor'] ) . '\'" onMouseOut="this.style.backgroundColor=\'' . esc_attr( $item['bgcolor'] ) . '\'"'; ?>>
				<div class="wp-mail-smtp-flyout-label"><?php echo esc_html( $item['title'] ); ?></div>
				<img src="<?php echo esc_url( $item['icon'] ); ?>" alt="<?php echo esc_attr( $item['title'] ); ?>">
			</a>
			<?php
		}
	}

	/**
	 * Menu items data.
	 *
	 * @since 3.0.0
	 *
	 * @return array Menu items data.
	 */
	private function menu_items() {

		$icons_url = wp_mail_smtp()->assets_url . '/images/flyout-menu';

		$items = [
			[
				'title'         => esc_html__( 'Upgrade to WP Mail SMTP Pro', 'wp-mail-smtp' ),
				'url'           => wp_mail_smtp()->get_upgrade_link( [ 'medium' => 'quick-link-menu' ] ),
				'icon'          => $icons_url . '/star.svg',
				'bgcolor'       => '#E27730',
				'hover_bgcolor' => '#B85A1B',
			],
			[
				'title' => esc_html__( 'Support & Docs', 'wp-mail-smtp' ),
				// phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
				'url'   => esc_url( wp_mail_smtp()->get_utm_url( 'https://wpmailsmtp.com/docs/', [ 'medium' => 'quick-link-menu', 'content' => 'Support' ] ) ),
				'icon'  => $icons_url . '/life-ring.svg',
			],
			[
				'title' => esc_html__( 'Follow on Facebook', 'wp-mail-smtp' ),
				'url'   => 'https://www.facebook.com/wpmailsmtp',
				'icon'  => $icons_url . '/facebook.svg',
			],
			[
				'title' => esc_html__( 'Suggest a Feature', 'wp-mail-smtp' ),
				// phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
				'url'   => esc_url( wp_mail_smtp()->get_utm_url( 'https://wpmailsmtp.com/suggest-a-feature/', [ 'medium' => 'quick-link-menu', 'content' => 'Feature' ] ) ),
				'icon'  => $icons_url . '/lightbulb.svg',
			],
		];

		if ( wp_mail_smtp()->is_pro() ) {
			array_shift( $items );
		}

		/**
		 * Filters quick links items.
		 *
		 * @since 3.0.0
		 *
		 * @param array $items {
		 *     Quick links items.
		 *
		 *     @type string $title         Item title.
		 *     @type string $url           Item link.
		 *     @type string $icon          Item icon url.
		 *     @type string $bgcolor       Item background color (optional).
		 *     @type string $hover_bgcolor Item background color on hover (optional).
		 * }
		 */
		return apply_filters( 'wp_mail_smtp_admin_flyout_menu_menu_items', $items );
	}
}
