<?php

namespace WPMailSMTP\Admin\Recommendations;

use WPMailSMTP\Admin\Area;

/**
 * Recommended-plugins rotating menu: catalog, rotation state, sidebar item.
 *
 * Surfaces one sister AM product at a time, advancing through the priority
 * list as each is adopted (a product is "adopted" 7 days after its plugin is
 * first activated). Once every product is adopted the item is hidden.
 *
 * @since 4.9.0
 */
class RecommendedPlugins {

	/**
	 * First-activation timestamps keyed by product slug.
	 *
	 * @since 4.9.0
	 */
	const ACTIVATED_OPTION = 'wp_mail_smtp_recommended_plugins_activated';

	/**
	 * A product is rotated past this long after its plugin was first activated.
	 *
	 * @since 4.9.0
	 */
	const ADOPTED_AFTER = 7 * DAY_IN_SECONDS;

	/**
	 * Register hooks for the recommended-plugins menu.
	 *
	 * @since 4.9.0
	 */
	public function hooks() {

		add_action( 'admin_init', [ $this, 'register_pages' ] );
		add_action( 'activated_plugin', [ $this, 'record_activation' ], 10, 2 );
	}

	/**
	 * Instantiate every product landing page so each can register its own
	 * AJAX and asset hooks.
	 *
	 * Runs on `admin_init` rather than synchronously: the catalog labels are
	 * translated, and building it before `init` triggers a textdomain-too-early
	 * notice on Pro, where the email log boots the admin during `plugins_loaded`.
	 *
	 * @since 4.9.0
	 */
	public function register_pages() {

		foreach ( $this->get_products() as $product ) {
			if ( class_exists( $product['page_class'] ) ) {
				new $product['page_class']();
			}
		}
	}

	/**
	 * Register the rotating submenu item for the currently surfaced product.
	 *
	 * Called by Area during its menu build so the item lands in the right
	 * position; does nothing once every product is adopted.
	 *
	 * @since 4.9.0
	 *
	 * @param string $access_capability Capability required to view the page.
	 */
	public function add_submenu_item( $access_capability ) {

		$product = $this->get_current_product();

		if ( $product === null ) {
			return;
		}

		add_submenu_page(
			Area::SLUG,
			$product['label'],
			$product['label'],
			$access_capability,
			$product['page_class']::SLUG,
			[ $this, 'render_current_page' ]
		);

		$short_slug = substr( $product['page_class']::SLUG, strlen( Area::SLUG . '-' ) );

		if ( ! in_array( $short_slug, Area::$pages_registered, true ) ) {
			Area::$pages_registered[] = $short_slug;
		}
	}

	/**
	 * Render the landing page for the currently surfaced product.
	 *
	 * @since 4.9.0
	 */
	public function render_current_page() {

		$product = $this->get_current_product();

		if ( $product === null ) {
			return;
		}

		$page = new $product['page_class']();

		// The id is required: all `wpms:` Tailwind utilities are build-scoped
		// under `#wp-mail-smtp`, so the landing markup must live beneath it.
		echo '<div class="wrap wp-mail-smtp-page" id="wp-mail-smtp">';
		$page->output();
		echo '</div>';
	}

	/**
	 * Ordered product catalog.
	 *
	 * @since 4.9.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_products() {

		return [
			[
				'slug'              => 'wpconsent',
				'name'              => 'WPConsent',
				'label'             => esc_html__( 'Privacy Compliance', 'wp-mail-smtp' ),
				'page_class'        => Pages\WPConsent::class,
				'plugin'            => 'wpconsent-cookies-banner-privacy-suite/wpconsent.php',
				'plugin_pro'        => 'wpconsent-premium/wpconsent-premium.php',
				'activation_option' => 'wpconsent_activated',
			],
			[
				'slug'              => 'activelayer',
				'name'              => 'ActiveLayer',
				'label'             => esc_html__( 'Spam Protection', 'wp-mail-smtp' ),
				'page_class'        => Pages\ActiveLayer::class,
				'plugin'            => 'activelayer-anti-spam-spam-protection-for-forms-comments/activelayer-anti-spam-spam-protection-for-forms-comments.php',
				'activation_option' => '', // No activation-time option exposed by the plugin.
			],
			[
				'slug'              => 'duplicator',
				'name'              => 'Duplicator',
				'label'             => esc_html__( 'Backups', 'wp-mail-smtp' ),
				'page_class'        => Pages\Duplicator::class,
				'plugin'            => 'duplicator/duplicator.php',
				'plugin_pro'        => 'duplicator-pro/duplicator-pro.php',
				'activation_option' => 'duplicator_install_info', // migration reads ['time'].
			],
			[
				'slug'              => 'wpvibe',
				'name'              => 'WPVibe',
				'label'             => esc_html__( 'AI MCP', 'wp-mail-smtp' ),
				'page_class'        => Pages\WPVibe::class,
				'plugin'            => 'vibe-ai/vibe-ai.php',
				'activation_option' => '', // No activation-time option exposed by the plugin.
			],
			[
				'slug'              => 'universally',
				'name'              => 'Universally',
				'label'             => esc_html__( 'Translations', 'wp-mail-smtp' ),
				'page_class'        => Pages\Universally::class,
				'plugin'            => 'universally-language-translation-multilingual-tool/universally.php',
				'activation_option' => '', // No activation-time option exposed by the plugin.
			],
			[
				'slug'              => 'wpcode',
				'name'              => 'WPCode',
				'label'             => esc_html__( 'Code Snippets', 'wp-mail-smtp' ),
				'page_class'        => Pages\WPCode::class,
				'plugin'            => 'insert-headers-and-footers/ihaf.php',
				'plugin_pro'        => 'wpcode-premium/wpcode.php',
				'activation_option' => 'ihaf_activated', // migration reads ['wpcode'].
			],
		];
	}

	/**
	 * Map of catalog plugin files (lite + pro) to product slug.
	 *
	 * @since 4.9.0
	 *
	 * @return array<string, string>
	 */
	private function get_plugin_slug_map() {

		$map = [];

		foreach ( $this->get_products() as $product ) {
			if ( ! empty( $product['plugin'] ) ) {
				$map[ $product['plugin'] ] = $product['slug'];
			}
			if ( ! empty( $product['plugin_pro'] ) ) {
				$map[ $product['plugin_pro'] ] = $product['slug'];
			}
		}

		return $map;
	}

	/**
	 * Record first activation time of a catalog plugin (write-once).
	 *
	 * @since 4.9.0
	 *
	 * @param string $plugin       Plugin file relative to the plugins dir.
	 * @param bool   $network_wide Whether activation is network-wide.
	 */
	public function record_activation( $plugin, $network_wide ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$slug = $this->get_plugin_slug_map()[ $plugin ] ?? '';

		if ( $slug === '' ) {
			return;
		}

		$activated = (array) get_option( self::ACTIVATED_OPTION, [] );

		if ( isset( $activated[ $slug ] ) ) {
			return;
		}

		$activated[ $slug ] = time();

		update_option( self::ACTIVATED_OPTION, $activated );
	}

	/**
	 * The product to surface right now, or null once every product is adopted.
	 *
	 * @since 4.9.0
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_current_product() {

		$products  = $this->get_products();
		$activated = (array) get_option( self::ACTIVATED_OPTION, [] );
		$now       = time();

		foreach ( $products as $product ) {
			$timestamp = isset( $activated[ $product['slug'] ] ) ? (int) $activated[ $product['slug'] ] : 0;

			if ( $timestamp === 0 || ( $now - $timestamp ) < self::ADOPTED_AFTER ) {
				return $product;
			}
		}

		// Everything adopted: surface nothing. The standard "About Us" submenu
		// remains as the trailing item.
		return null;
	}
}
