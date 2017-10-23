<?php

namespace WPMailSMTP\Providers;

/**
 * Class Mail
 */
class Mail extends ProviderAbstract {

	/**
	 * @inheritdoc
	 */
	public function get_logo_url() {
		return wp_mail_smtp()->plugin_url . '/assets/images/php.png';
	}

	/**
	 * @inheritdoc
	 */
	public function get_slug() {
		return 'mail';
	}

	/**
	 * @inheritdoc
	 */
	public function get_title() {
		return __( 'Default (none)', 'wp-mail-smtp' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return '';
	}

	/**
	 * @inheritdoc
	 */
	public function display_options() {
		?>

		<blockquote>
			<?php _e( 'You currently have the native WordPress option selected. Please select an SMTP above to begin setup.', 'wp-mail-smtp' ); ?>
		</blockquote>

		<?php
	}

}
