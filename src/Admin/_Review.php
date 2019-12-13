<?php

namespace WPMailSMTP\Admin;

/**
 * Class Review.
 *
 * @since 1.8.0
 */
class Review {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.8.0
	 */
	public function __construct() {

		// Admin notice requesting review.
		add_action( 'admin_notices', array( $this, 'review_request' ) );
		add_action( 'wp_ajax_wp_mail_smtp_review_dismiss', array( $this, 'review_dismiss' ) );
	}

	/**
	 * Add admin notices as needed for reviews.
	 *
	 * @since 1.8.0
	 */
	public function review_request() {

		// Only consider showing the review request to admin users.
		if ( ! is_super_admin() ) {
			return;
		}

		// If the user has opted out of product annoucement notifications, don't
		// display the review request.
		if ( wp_mail_smtp_get_option( 'hide_am_notices', false ) || wp_mail_smtp_get_option( 'network_hide_am_notices', false ) ) {
			return;
		}

		// Verify that we can do a check for reviews.
		$review = get_option( 'wp_mail_smtp_review' );
		$time   = time();
		$load   = false;

		if ( ! $review ) {
			$review = array(
				'time'      => $time,
				'dismissed' => false,
			);
			update_option( 'wp_mail_smtp_review', $review );
		} else {
			// Check if it has been dismissed or not.
			if ( ( isset( $review['dismissed'] ) && ! $review['dismissed'] ) && ( isset( $review['time'] ) && ( ( $review['time'] + DAY_IN_SECONDS ) <= $time ) ) ) {
				$load = true;
			}
		}

		// If we cannot load, return early.
		if ( ! $load ) {
			return;
		}

		$this->review();
	}

	/**
	 * Maybe show review request.
	 *
	 * @since 1.8.0
	 */
	public function review() {

		// Fetch when plugin was initially installed.
		$activated = get_option( 'wp_mail_smtp_over_time', array() );
		$ua_code   = wp_mail_smtp_get_ua();

		if ( ! empty( $activated['connected_date'] ) ) {
			// Only continue if plugin has been tracking for at least 14 days.
			if ( ( $activated['connected_date'] + ( DAY_IN_SECONDS * 14 ) ) > time() ) {
				return;
			}
		} else {
			if ( empty( $activated ) ) {
				$data = array(
					'installed_version' => MONSTERINSIGHTS_VERSION,
					'installed_date'    => time(),
					'installed_pro'     => wp_mail_smtp_is_pro_version(),
				);
			} else {
				$data = $activated;
			}
			// If already has a UA code mark as connected now.
			if ( ! empty( $ua_code ) ) {
				$data['connected_date'] = time();
			}

			update_option( 'wp_mail_smtp_over_time', $data );

			return;
		}

		// Only proceed with displaying if the user is tracking.
		if ( empty( $ua_code ) ) {
			return;
		}

		$feedback_url = add_query_arg( array(
			                               'wpf192157_24' => untrailingslashit( home_url() ),
			                               'wpf192157_26' => wp_mail_smtp_get_license_key(),
			                               'wpf192157_27' => wp_mail_smtp_is_pro_version() ? 'pro' : 'lite',
			                               'wpf192157_28' => MONSTERINSIGHTS_VERSION,
		                               ), 'https://www.wp_mail_smtp.com/plugin-feedback/' );
		$feedback_url = wp_mail_smtp_get_url( 'review-notice', 'feedback', $feedback_url );
		// We have a candidate! Output a review message.
		?>
		<div class="notice notice-info is-dismissible wp_mail_smtp-review-notice">
			<div class="wp_mail_smtp-review-step wp_mail_smtp-review-step-1">
				<p><?php esc_html_e( 'Are you enjoying MonsterInsights?', 'google-analytics-for-wordpress' ); ?></p>
				<p>
					<a href="#" class="wp_mail_smtp-review-switch-step"
						data-step="3"><?php esc_html_e( 'Yes', 'google-analytics-for-wordpress' ); ?></a><br />
					<a href="#" class="wp_mail_smtp-review-switch-step"
						data-step="2"><?php esc_html_e( 'Not Really', 'google-analytics-for-wordpress' ); ?></a>
				</p>
			</div>
			<div class="wp_mail_smtp-review-step wp_mail_smtp-review-step-2" style="display: none">
				<p><?php esc_html_e( 'We\'re sorry to hear you aren\'t enjoying MonsterInsights. We would love a chance to improve. Could you take a minute and let us know what we can do better?', 'google-analytics-for-wordpress' ); ?></p>
				<p>
					<a href="<?php echo esc_url( $feedback_url ); ?>"
						class="wp_mail_smtp-dismiss-review-notice wp_mail_smtp-review-out"><?php esc_html_e( 'Give Feedback', 'google-analytics-for-wordpress' ); ?></a><br>
					<a href="#" class="wp_mail_smtp-dismiss-review-notice" target="_blank"
						rel="noopener noreferrer"><?php esc_html_e( 'No thanks', 'google-analytics-for-wordpress' ); ?></a>
				</p>
			</div>
			<div class="wp_mail_smtp-review-step wp_mail_smtp-review-step-3" style="display: none">
				<p><?php esc_html_e( 'That’s awesome! Could you please do me a BIG favor and give it a 5-star rating on WordPress to help us spread the word and boost our motivation?', 'google-analytics-for-wordpress' ); ?></p>
				<p>
					<strong><?php echo wp_kses( __( '~ Syed Balkhi<br>Co-Founder of MonsterInsights', 'google-analytics-for-wordpress' ), array( 'br' => array() ) ); ?></strong>
				</p>
				<p>
					<a href="https://wordpress.org/support/plugin/google-analytics-for-wordpress/reviews/?filter=5#new-post"
						class="wp_mail_smtp-dismiss-review-notice wp_mail_smtp-review-out" target="_blank"
						rel="noopener noreferrer"><?php esc_html_e( 'Ok, you deserve it', 'google-analytics-for-wordpress' ); ?></a><br>
					<a href="#" class="wp_mail_smtp-dismiss-review-notice" target="_blank"
						rel="noopener noreferrer"><?php esc_html_e( 'Nope, maybe later', 'google-analytics-for-wordpress' ); ?></a><br>
					<a href="#" class="wp_mail_smtp-dismiss-review-notice" target="_blank"
						rel="noopener noreferrer"><?php esc_html_e( 'I already did', 'google-analytics-for-wordpress' ); ?></a>
				</p>
			</div>
		</div>
		<script type="text/javascript">
			jQuery( document ).ready( function ( $ ) {
				$( document ).on( 'click', '.wp_mail_smtp-dismiss-review-notice, .wp_mail_smtp-review-notice button', function ( event ) {
					if ( !$( this ).hasClass( 'wp_mail_smtp-review-out' ) ) {
						event.preventDefault();
					}
					$.post( ajaxurl, {
						action: 'wp_mail_smtp_review_dismiss',
					} );
					$( '.wp_mail_smtp-review-notice' ).remove();
				} );

				$( document ).on( 'click', '.wp_mail_smtp-review-switch-step', function ( e ) {
					e.preventDefault();
					var target = $( this ).attr( 'data-step' );
					if ( target ) {
						var notice = $( this ).closest( '.wp_mail_smtp-review-notice' );
						var review_step = notice.find( '.wp_mail_smtp-review-step-' + target );
						if ( review_step.length > 0 ) {
							notice.find( '.wp_mail_smtp-review-step:visible' ).fadeOut( function () {
								review_step.fadeIn();
							} );
						}
					}
				} );
			} );
		</script>
		<?php
	}

	/**
	 * Dismiss the review admin notice
	 *
	 * @since 1.8.0
	 */
	public function review_dismiss() {

		$review              = get_option( 'wp_mail_smtp_review', array() );
		$review['time']      = time();
		$review['dismissed'] = true;
		update_option( 'wp_mail_smtp_review', $review );

		if ( is_super_admin() && is_multisite() ) {
			$site_list = get_sites();
			foreach ( (array) $site_list as $site ) {
				switch_to_blog( $site->blog_id );

				update_option( 'wp_mail_smtp_review', $review );

				restore_current_blog();
			}
		}

		die;
	}
}
