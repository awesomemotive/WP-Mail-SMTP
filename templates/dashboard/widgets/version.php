<?php
/**
 * Version strip body.
 *
 * @since 4.10.0
 *
 * @var string $variant        Template variant: 'data' (Pro), 'update' (Pro, a newer version
 *                             is waiting), 'license' (Pro, the license needs attention) or
 *                             'education' (Lite).
 * @var string $product_name   Displayed product name.
 * @var string $version        Installed plugin version.
 * @var string $license_label  License CTA label (license variant).
 * @var string $license_url    License CTA URL (license variant).
 * @var string $license_notice What the license state means for the site, as escaped markup
 *                             (license variant).
 * @var string $license_tone   Severity of that state: 'error' or 'warning' (license variant).
 * @var string $update_url     Plugin update link (update variant).
 * @var string $upgrade_url    Upgrade link (Lite).
 * @var string $whats_new_url  Changelog documentation URL (Pro).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cta_color = $license_tone === 'error' ? 'wp-mail-smtp-btn-red' : 'wp-mail-smtp-btn-orange';
?>
<div class="wpms-dashboard-version">
	<div class="wpms-dashboard-version__row">
		<span class="wpms-dashboard-version__label">
			<?php if ( $variant === 'license' ) : ?>
				<i class="wpms-dashboard-version__alert wpms:icon-[fa6-solid--circle-exclamation] wpms:w-[16px] wpms:h-[16px]" aria-hidden="true"></i>
			<?php elseif ( $variant !== 'education' ) : ?>
				<i class="wpms-dashboard-version__crown wpms:icon-[fa6-solid--crown] wpms:w-[16px] wpms:h-[16px]" aria-hidden="true"></i>
			<?php endif; ?>

			<span class="wpms-dashboard-version__name"><?php echo esc_html( $product_name ); ?></span>
			<span class="wpms-dashboard-version__number"><?php echo esc_html( $version ); ?></span>
		</span>

		<?php if ( $variant === 'data' ) : ?>
			<a href="<?php echo esc_url( $whats_new_url ); ?>" class="wpms-text-link wpms-dashboard-version__link"
				target="_blank" rel="noopener noreferrer">
				<?php echo esc_html__( "What's New", 'wp-mail-smtp' ); ?>
			</a>
		<?php elseif ( $variant === 'update' ) : ?>
			<a href="<?php echo esc_url( $update_url ); ?>"
				class="wp-mail-smtp-btn wp-mail-smtp-btn-sm wpms-dashboard-version__cta wpms-dashboard-version__cta--update">
				<?php echo esc_html__( 'Update Now', 'wp-mail-smtp' ); ?>
			</a>
		<?php elseif ( $variant === 'license' ) : ?>
			<a href="<?php echo esc_url( $license_url ); ?>"
				class="wp-mail-smtp-btn wp-mail-smtp-btn-sm <?php echo esc_attr( $cta_color ); ?> wpms-dashboard-version__cta">
				<?php echo esc_html( $license_label ); ?>
			</a>
		<?php else : ?>
			<a href="<?php echo esc_url( $upgrade_url ); ?>"
				class="wp-mail-smtp-btn wp-mail-smtp-btn-sm wp-mail-smtp-btn-orange wpms-dashboard-version__cta"
				target="_blank" rel="noopener noreferrer">
				<?php echo esc_html__( 'Upgrade to Pro', 'wp-mail-smtp' ); ?>
			</a>
		<?php endif; ?>
	</div>

	<?php if ( $variant === 'license' && $license_notice !== '' ) : ?>
		<p class="wpms-dashboard-version__notice">
			<?php
			// Built from escaped strings.
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $license_notice;
			?>
		</p>
	<?php endif; ?>
</div>
