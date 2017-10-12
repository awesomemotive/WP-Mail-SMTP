<?php

namespace WPMailSMTP\Admin;

use WPMailSMTP\WP;

/**
 * Class Test is part of Area, displays email testing page of the plugin.
 */
class Test extends PageAbstract {

	/**
	 * @var string Slug of a subpage.
	 */
	public $slug = 'test';

	/**
	 * Test constructor.
	 */
	public function __construct() {
	}

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return __( 'Email Test', 'wp-mail-smtp' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_title() {
		return __( 'Send a Test Email', 'wp-mail-smtp' );
	}

	/**
	 * @inheritdoc
	 */
	public function display() {
		?>

		<form method="POST" action="">
			<?php wp_nonce_field( 'wp-mail-smtp-test' ); ?>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="wp-mail-smtp-test-email"><?php _e( 'To', 'wp-mail-smtp' ); ?></label>
					</th>
					<td>
						<input name="wp-mail-smtp[test_email]" type="email" id="wp-mail-smtp-test-email" required class="regular-text" spellcheck="false" />
						<p class="description"><?php _e( 'Type an email address here and then click a button below to generate a test email.', 'wp-mail-smtp' ); ?></p>
					</td>
				</tr>
			</table>

			<p class="submit">
				<input type="submit" name="wp-mail-smtp[test_submit]" class="button-primary" value="<?php _e( 'Send Email', 'wp-mail-smtp' ); ?>"/>
			</p>
		</form>

		<?php
	}

	/**
	 * @inheritdoc
	 */
	public function process( $data ) {
		check_admin_referer( 'wp-mail-smtp-test' );

		//pvar( $data );

		if ( isset( $data['test_email'] ) ) {
			$data['test_email'] = filter_var( $data['test_email'], FILTER_VALIDATE_EMAIL );
		}

		if (
			! isset( $data['test_submit'] ) ||
			empty( $data['test_email'] )
		) {
			WP::add_admin_notice(
				__( 'Test failed. Please complete the form and try to resend the test email.', 'wp-mail-smtp' ),
				WP::ADMIN_NOTICE_WARNING
			);
			return;
		}

		/*
		 * Do the actual sending.
		 */

		WP::add_admin_notice(
			__( 'Your email was sent successfully!', 'wp-mail-smtp' ),
			WP::ADMIN_NOTICE_SUCCESS
		);

		//pvar( '----------------', 1 );
	}
}
