<?php

namespace WPMailSMTP\Providers;

/**
 * Class Mail
 */
class Mail extends ProviderAbstract {

	/**
	 * Mail constructor.
	 */
	public function __construct() {

		parent::__construct(
			array(
				'logo_url' => wp_mail_smtp()->plugin_url . '/assets/images/php.png',
				'slug'     => 'mail',
				'title'    => __( 'Default (none)', 'wp-mail-smtp' ),
			)
		);
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
