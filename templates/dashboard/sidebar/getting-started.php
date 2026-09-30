<?php
/**
 * Dashboard "Getting Started" sidebar widget body.
 *
 * @since 4.10.0
 *
 * @var array $links    Documentation links: each item has `label` and `url`.
 * @var array $view_all "View All Documentation" link: `label` and `url`.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_mail_smtp_render() returns escaped HTML.
echo wp_mail_smtp_render( 'dashboard/doc-list', [ 'links' => $links ], true );
?>
<div class="wpms-dashboard-getting-started-footer">
	<a class="wpms-text-link wpms-text-link--arrow wpms-text-link--md" href="<?php echo esc_url( $view_all['url'] ); ?>" target="_blank" rel="noopener noreferrer">
		<span class="wpms-text-link__label"><?php echo esc_html( $view_all['label'] ); ?></span>
		<i class="wpms-text-link__icon wpms:icon-[fa6-solid--arrow-right] wpms:w-[14px] wpms:h-[14px]" aria-hidden="true"></i>
	</a>
</div>
