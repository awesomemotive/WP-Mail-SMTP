<?php
/**
 * Dashboard shared documentation list, each row rendered by the `doc-link` partial.
 *
 * @since 4.10.0
 *
 * @var array $links Documentation links: each item has `label` and `url`.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<ul class="wpms-dashboard-doc-list">
	<?php foreach ( $links as $doc_link ) : ?>
		<?php
		// Filtered data: skip items without a URL or label.
		if ( empty( $doc_link['url'] ) || empty( $doc_link['label'] ) ) {
			continue;
		}
		?>
		<li>
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_mail_smtp_render() returns escaped HTML.
			echo wp_mail_smtp_render( 'dashboard/doc-link', $doc_link, true );
			?>
		</li>
	<?php endforeach; ?>
</ul>
