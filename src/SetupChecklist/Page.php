<?php

namespace WPMailSMTP\SetupChecklist;

use WPMailSMTP\Admin\ProFeatures;
use WPMailSMTP\SettingsImport\SettingsImport;

/**
 * Setup Checklist admin page controller.
 *
 * @since 4.10.0
 */
class Page {

	/**
	 * Admin page slug.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	public const SLUG = 'wp-mail-smtp-setup-checklist';

	/**
	 * Checklist model.
	 *
	 * @since 4.10.0
	 *
	 * @var Checklist
	 */
	private $checklist;

	/**
	 * Promo sections model (the Pro upsell and the section chrome).
	 *
	 * @since 4.10.0
	 *
	 * @var Promos
	 */
	private $promos;

	/**
	 * The partner plugins this page recommends.
	 *
	 * @since 4.10.0
	 *
	 * @var GrowthTools
	 */
	private $growth_tools;

	/**
	 * Constructor.
	 *
	 * @since 4.10.0
	 *
	 * @param Checklist   $checklist    Checklist model.
	 * @param Promos      $promos       Promo sections model.
	 * @param GrowthTools $growth_tools The partner plugins this page recommends.
	 */
	public function __construct( Checklist $checklist, Promos $promos, GrowthTools $growth_tools ) {

		$this->checklist    = $checklist;
		$this->promos       = $promos;
		$this->growth_tools = $growth_tools;
	}

	/**
	 * Register hooks, only while the current request is the checklist page.
	 *
	 * @since 4.10.0
	 */
	public function hooks(): void {

		if ( ! wp_mail_smtp()->get_admin()->is_admin_page( 'setup-checklist' ) ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'in_admin_header', [ $this, 'hide_admin_notices' ], PHP_INT_MAX );
	}

	/**
	 * Suppress all admin notices on the checklist page.
	 *
	 * WordPress relocates a notice printed after the first <h1> into the title bar, so
	 * this runs on `in_admin_header`, before the `admin_notices` hooks fire.
	 *
	 * @since 4.10.0
	 */
	public function hide_admin_notices(): void {

		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
	}

	/**
	 * Get the page's stable admin URL.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public static function get_url(): string {

		return wp_mail_smtp()->get_admin()->get_admin_page_url( self::SLUG );
	}

	/**
	 * Enqueue the page assets.
	 *
	 * @since 4.10.0
	 */
	public function enqueue_assets(): void {

		wp_enqueue_style(
			'wp-mail-smtp-setup-checklist',
			wp_mail_smtp()->assets_url . '/css/smtp-setup-checklist.min.css',
			[ 'wp-mail-smtp-admin' ],
			WPMS_PLUGIN_VER
		);

		wp_enqueue_script(
			'wp-mail-smtp-setup-checklist',
			wp_mail_smtp()->assets_url . '/js/smtp-setup-checklist.min.js',
			[ 'jquery', 'wp-mail-smtp-admin', 'wp-mail-smtp-admin-jconfirm' ],
			WPMS_PLUGIN_VER,
			true
		);

		wp_localize_script(
			'wp-mail-smtp-setup-checklist',
			'wp_mail_smtp_setup_checklist',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'error'    => esc_html__( 'Something went wrong. Please try again.', 'wp-mail-smtp' ),
				'dismiss'  => [
					'title'   => esc_html__( 'Are you sure?', 'wp-mail-smtp' ),
					'content' => esc_html__( 'This will permanently dismiss the Setup Checklist, and you will not be able to bring it back. This cannot be undone.', 'wp-mail-smtp' ),
					'confirm' => esc_html__( 'Yes, dismiss', 'wp-mail-smtp' ),
					'cancel'  => esc_html__( 'Cancel', 'wp-mail-smtp' ),
					'error'   => esc_html__( 'Something went wrong dismissing the checklist. Please try again.', 'wp-mail-smtp' ),
				],
				'plugins'  => [
					'installing' => esc_html__( 'Installing…', 'wp-mail-smtp' ),
					'activating' => esc_html__( 'Activating…', 'wp-mail-smtp' ),
					'activate'   => esc_html__( 'Activate', 'wp-mail-smtp' ),
					'installed'  => esc_html__( 'Installed', 'wp-mail-smtp' ),
				],
			]
		);
	}

	/**
	 * Render the page.
	 *
	 * @since 4.10.0
	 */
	public function output(): void {

		echo '<div class="wrap wp-mail-smtp-page" id="wp-mail-smtp">';

		$this->render_page(
			$this->view_sections(),
			wp_mail_smtp()->is_pro() ? [] : [
				'tiles'       => ( new ProFeatures() )->get_tiles(),
				'upgrade_cta' => $this->promos->upgrade_cta(),
			],
			[
				'tiles' => $this->growth_tools->get_tiles(),
			]
		);

		echo '</div>';
	}

	/**
	 * Render the checklist page markup: the section list plus the promo sections.
	 *
	 * @since 4.10.0
	 *
	 * @param array $sections     Sections, each with `id`, `title`, `is_complete`, and `items`
	 *                            (item view-models tagged `type` => `item`).
	 * @param array $features     Feature promo (Lite only, empty on Pro): `tiles` and
	 *                            `upgrade_cta` (`label`, `url`, `chip`).
	 * @param array $growth_tools Growth-tools promo: `tiles`.
	 */
	private function render_page( array $sections, array $features, array $growth_tools ): void {

		?>
		<div class="wpms-setup-checklist-title-bar">
			<h1 class="wpms-setup-checklist-title-bar__title">
				<?php esc_html_e( 'Complete WP Mail SMTP Setup Checklist', 'wp-mail-smtp' ); ?>
			</h1>
		</div>

		<div class="wp-mail-smtp-page-content wpms-setup-checklist">
		<?php
		foreach ( $sections as $section ) :
			$items_id        = 'wpms-setup-checklist-section-' . sanitize_html_class( $section['id'] );
			$section_classes = [ 'wpms-card', 'wpms-setup-checklist-section' ];

			if ( $section['is_complete'] ) {
				$section_classes[] = 'is-complete';
				$section_classes[] = 'is-collapsed';
			}

			$badge_classes = $section['is_complete'] ? 'wpms-badge wpms-badge--complete' : 'wpms-badge';
			$badge_label   = $section['is_complete'] ? __( 'Complete', 'wp-mail-smtp' ) : __( 'Incomplete', 'wp-mail-smtp' );
			?>
			<section class="<?php echo esc_attr( implode( ' ', $section_classes ) ); ?>" data-section="<?php echo esc_attr( $section['id'] ); ?>">
				<div class="wpms-card__head wpms-setup-checklist-section__header">
					<h2 class="wpms-card__title"><?php echo esc_html( $section['title'] ); ?></h2>
					<?php
					printf(
						'<span class="%1$s">%2$s</span>',
						esc_attr( $badge_classes ),
						esc_html( $badge_label )
					);
					?>
					<button type="button" class="wpms-icon-btn wpms-setup-checklist-section__toggle" aria-expanded="<?php echo $section['is_complete'] ? 'false' : 'true'; ?>" aria-controls="<?php echo esc_attr( $items_id ); ?>">
						<i class="wpms-setup-checklist-toggle-icon wpms:icon-[fa6-solid--chevron-down] wpms:w-[12px] wpms:h-[12px]" aria-hidden="true"></i>
						<span class="screen-reader-text"><?php esc_html_e( 'Toggle section', 'wp-mail-smtp' ); ?></span>
					</button>
				</div>
				<div class="wpms-setup-checklist-section__items" id="<?php echo esc_attr( $items_id ); ?>">
					<div class="wpms-setup-checklist-section__items-inner">
						<?php
						foreach ( $section['items'] as $item ) {
							$this->render_item( $item );
						}
						?>
					</div>
				</div>
			</section>
		<?php endforeach; ?>

		<?php if ( ! empty( $features ) ) : ?>
		<section class="wpms-card wpms-setup-checklist-promo" data-promo="features">
			<?php
			$this->promos->render_header(
				'wpms-setup-checklist-promo-features',
				__( 'Take Your Email Deliverability to the Next Level', 'wp-mail-smtp' )
			);
			?>
			<div class="wpms-setup-checklist-promo__body" id="wpms-setup-checklist-promo-features">
				<div class="wpms-setup-checklist-promo__body-inner">
					<div class="wpms-setup-checklist-promo__grid">
						<?php
						foreach ( $features['tiles'] as $tile ) {
							echo wp_mail_smtp_render( 'feature-tile', [ 'tile' => $tile ], true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_mail_smtp_render() returns escaped HTML.
						}
						?>
					</div>
					<?php $this->promos->render_footer( $features['upgrade_cta'] ); ?>
				</div>
			</div>
		</section>
		<?php endif; ?>

		<section class="wpms-card wpms-setup-checklist-promo" data-promo="growth-tools">
			<?php
			$this->promos->render_header(
				'wpms-setup-checklist-promo-growth-tools',
				__( 'Set Up Recommended Growth Tools', 'wp-mail-smtp' ),
				__( 'Take your website to the next level with our sister plugins.', 'wp-mail-smtp' )
			);
			?>
			<div class="wpms-setup-checklist-promo__body" id="wpms-setup-checklist-promo-growth-tools">
				<div class="wpms-setup-checklist-promo__body-inner">
					<div class="wpms-setup-checklist-promo__grid">
						<?php
						foreach ( $growth_tools['tiles'] as $tile ) {
							echo wp_mail_smtp_render( 'feature-tile', [ 'tile' => $tile ], true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_mail_smtp_render() returns escaped HTML.
						}
						?>
					</div>
				</div>
			</div>
		</section>

		<div class="wpms-setup-checklist-footer">
			<a href="#" class="wpms-setup-checklist-dismiss" data-action="dismiss">
				<?php esc_html_e( 'Complete Setup Checklist', 'wp-mail-smtp' ); ?>
			</a>
		</div>

		<?php // Where the shared SendLayer quick-connect handler sends the user back to. ?>
		<input type="hidden" id="wp-mail-smtp-sendlayer-quick-connect-return-url" value="<?php echo esc_url( self::get_url() ); ?>">
		</div>
		<?php
	}

	/**
	 * Render one checklist item row.
	 *
	 * @since 4.10.0
	 *
	 * @param array $item Item view-model: `id`, `title`, `description` (may carry inline
	 *                    markup, so it renders through `wp_kses_post()`), `is_complete`,
	 *                    `status_label`, `form`, `learn_more` (optional `label`/`url`),
	 *                    and `ctas` (array of resolved, render-ready CTAs).
	 */
	private function render_item( array $item ): void {

		$is_complete = ! empty( $item['is_complete'] );
		$classes     = [ 'wpms-setup-checklist-item' ];

		if ( $is_complete ) {
			$classes[] = 'is-complete';
		}

		$check_classes = [
			'wpms-setup-checklist-item__check',
			$is_complete ? 'wpms:icon-[fa6-regular--circle-check]' : 'wpms:icon-[fa6-regular--circle]',
			'wpms:w-[24px]',
			'wpms:h-[24px]',
		];

		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-item="<?php echo esc_attr( $item['id'] ); ?>">
			<span class="<?php echo esc_attr( implode( ' ', $check_classes ) ); ?>" aria-hidden="true"></span>
			<div class="wpms-setup-checklist-item__body">
				<span class="screen-reader-text"><?php echo esc_html( $item['status_label'] ); ?></span>
				<h3 class="wpms-setup-checklist-item__title"><?php echo esc_html( $item['title'] ); ?></h3>
				<p class="wpms-setup-checklist-item__description">
					<?php echo wp_kses_post( $item['description'] ); ?>
					<?php if ( ! empty( $item['learn_more'] ) ) : ?>
						<a class="wpms-setup-checklist-item__learn-more" href="<?php echo esc_url( $item['learn_more']['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $item['learn_more']['label'] ); ?></a>
					<?php endif; ?>
				</p>
			</div>
			<div class="wpms-setup-checklist-item__actions">
				<?php
				if ( ! $is_complete ) {
					$this->render_item_form_field( $item['form'] );
				}

				foreach ( $item['ctas'] as $cta ) {
					$this->render_item_button( $cta );
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the form field an item's CTA needs before it can act, if any.
	 *
	 * @since 4.10.0
	 *
	 * @param string $form Item form key: `import` for the SMTP-plugin picker feeding
	 *                     the "Import Settings" CTA, `email` for the newsletter signup
	 *                     address feeding the "Sign Up for Free" CTA, or empty for none.
	 */
	private function render_item_form_field( string $form ): void {

		if ( $form === 'import' ) {
			?>
			<select class="wpms-setup-checklist-item__select js-wpms-setup-checklist-import-select" aria-label="<?php esc_attr_e( 'Select the SMTP plugin to import settings from', 'wp-mail-smtp' ); ?>">
				<option value=""><?php esc_html_e( 'Select SMTP Plugin', 'wp-mail-smtp' ); ?></option>
				<?php foreach ( ( new SettingsImport() )->get_detected_plugins() as $slug => $name ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php
		} elseif ( $form === 'email' ) {
			?>
			<input type="email" class="wpms-setup-checklist-item__input js-wpms-setup-checklist-newsletter-email" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" aria-label="<?php esc_attr_e( 'Your email address', 'wp-mail-smtp' ); ?>">
			<?php
		}
	}

	/**
	 * Render one item CTA button.
	 *
	 * @since 4.10.0
	 *
	 * @param array $cta CTA data: `label`, `url`, and optional `icon` (Iconify utility classes),
	 *                   `modifier` (button colour variant), `action` plus `plugin` (drive the
	 *                   install JS handler), `reload` (reload the page on AJAX success),
	 *                   `icon_after` (Iconify utility classes for a trailing glyph),
	 *                   `class` (extra classes, e.g. a `js-` hook an existing handler binds to),
	 *                   `data` (extra data attributes keyed by attribute name without the `data-`
	 *                   prefix), `disabled` (render as inert: no href, no click handlers, greyed
	 *                   out via CSS), and `tooltip` (hover text explaining the button's state).
	 */
	private function render_item_button( array $cta ): void {

		$variants = [
			'secondary' => 'wp-mail-smtp-btn-secondary',
			'grey'      => 'wp-mail-smtp-btn-light-grey',
		];

		$classes = [
			'wp-mail-smtp-btn',
			'wp-mail-smtp-btn-md',
			$variants[ $cta['modifier'] ?? '' ] ?? 'wp-mail-smtp-btn-orange',
			'wpms-setup-checklist-item__button',
			$cta['class'] ?? '',
		];

		$has_tooltip = ! empty( $cta['tooltip'] );

		if ( $has_tooltip ) {
			echo '<span class="wp-mail-smtp-tooltip wpms-setup-checklist-item__tooltip">';
		}

		?>
		<a class="<?php echo esc_attr( implode( ' ', array_filter( $classes ) ) ); ?>"<?php $this->render_item_button_attributes( $cta ); ?>>
			<?php if ( ! empty( $cta['icon'] ) ) : ?>
				<i class="<?php echo esc_attr( $cta['icon'] ); ?>" aria-hidden="true"></i>
			<?php endif; ?>
			<?php echo esc_html( $cta['label'] ); ?>
			<?php if ( ! empty( $cta['icon_after'] ) ) : ?>
				<i class="<?php echo esc_attr( $cta['icon_after'] ); ?>" aria-hidden="true"></i>
			<?php endif; ?>
		</a>
		<?php

		if ( $has_tooltip ) {
			printf(
				'<span class="wp-mail-smtp-tooltip-text wp-mail-smtp-tooltip-small-text">%s</span></span>',
				esc_html( $cta['tooltip'] )
			);
		}
	}

	/**
	 * Print a CTA anchor's attributes, the class aside.
	 *
	 * @since 4.10.0
	 *
	 * @param array $cta CTA data, as described on {@see self::render_item_button()}.
	 */
	private function render_item_button_attributes( array $cta ): void {

		if ( empty( $cta['disabled'] ) ) {
			printf( ' href="%s"', esc_url( $cta['url'] ?? '#' ) );

			foreach ( $this->get_item_button_data_attributes( $cta ) as $name => $value ) {
				printf( ' data-%1$s="%2$s"', esc_attr( $name ), esc_attr( $value ) );
			}
		} else {
			// No href: an anchor without one is neither clickable nor focusable.
			echo ' aria-disabled="true"';
		}
	}

	/**
	 * The data attributes an active CTA hands to its JS handler.
	 *
	 * @since 4.10.0
	 *
	 * @param array $cta CTA data, as described on {@see self::render_item_button()}.
	 *
	 * @return array Attribute values keyed by name, without the `data-` prefix.
	 */
	private function get_item_button_data_attributes( array $cta ): array {

		$data = [];

		foreach ( [ 'action', 'plugin' ] as $key ) {
			if ( ! empty( $cta[ $key ] ) ) {
				$data[ $key ] = $cta[ $key ];
			}
		}

		if ( ! empty( $cta['reload'] ) ) {
			$data['reload'] = '1';
		}

		return array_merge( $data, $cta['data'] ?? [] );
	}

	/**
	 * Build the per-section view-model: completion state plus each item resolved for render.
	 *
	 * @since 4.10.0
	 *
	 * @return array<int, array>
	 */
	private function view_sections(): array {

		$sections = [];

		foreach ( $this->checklist->get_sections() as $section ) {
			$items = [];

			foreach ( $section['items'] as $item ) {
				$items[] = $this->view_item( $item );
			}

			$sections[] = [
				'id'          => $section['id'],
				'title'       => $section['title'],
				'is_complete' => ! empty( $section['complete'] ),
				'items'       => $items,
			];
		}

		return $sections;
	}

	/**
	 * Build a single item view-model.
	 *
	 * @since 4.10.0
	 *
	 * @param array $item Item enriched with completion state.
	 *
	 * @return array
	 */
	private function view_item( array $item ): array {

		$is_complete = ! empty( $item['complete'] );

		return [
			'type'         => 'item',
			'id'           => $item['id'],
			'title'        => $item['title'],
			'description'  => $item['description'],
			'is_complete'  => $is_complete,
			'status_label' => $is_complete ? __( 'Completed', 'wp-mail-smtp' ) : __( 'Not completed', 'wp-mail-smtp' ),
			'ctas'         => $is_complete ? [] : $this->resolve_ctas( $item ),
			'form'         => $item['form'] ?? '',
			'learn_more'   => $item['learn_more'] ?? null,
		];
	}

	/**
	 * Resolve an item's CTAs to their renderable shapes, dropping the ones that resolve empty.
	 *
	 * @since 4.10.0
	 *
	 * @param array $item Item enriched with completion state.
	 *
	 * @return array<int, array>
	 */
	private function resolve_ctas( array $item ): array {

		$ctas     = $item['ctas'] ?? ( ! empty( $item['cta'] ) ? [ $item['cta'] ] : [] );
		$resolved = [];

		foreach ( $ctas as $cta ) {
			$cta = $this->resolve_cta( $cta );

			if ( ! empty( $cta ) ) {
				$resolved[] = $cta;
			}
		}

		return $resolved;
	}

	/**
	 * Resolve a CTA to its renderable shape.
	 *
	 * @since 4.10.0
	 *
	 * @param array $cta CTA data.
	 *
	 * @return array Renderable CTA data for the item-button template, or empty.
	 */
	private function resolve_cta( array $cta ): array {

		return empty( $cta['label'] ) ? [] : $cta;
	}
}
