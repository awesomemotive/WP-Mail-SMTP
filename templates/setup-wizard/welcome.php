<?php
/**
 * The Setup Wizard's welcome step: a standalone document rendered outside the admin
 * enqueue pipeline, which is why it carries its own stylesheet link and inline script.
 *
 * @since 4.10.0
 *
 * @var string $settings            Start-request settings, JSON-encoded for the inline script.
 * @var string $css_url             Wizard stylesheet URL.
 * @var string $logo_url            Plugin logo URL.
 * @var string $loading_url         Loading indicator URL.
 * @var string $settings_url        Plugin settings page URL, for the exit link.
 * @var bool   $is_local_environment Whether this site runs the bundled wizard, with nothing to transfer to.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width" />
	<meta name="robots" content="noindex,nofollow" />
	<title><?php esc_html_e( 'WP Mail SMTP &rsaquo; Setup Wizard', 'wp-mail-smtp' ); ?></title>
	<?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
	<link rel="stylesheet" href="<?php echo esc_url( $css_url ); ?>" />
	<style>
		.wp-mail-smtp-loading[hidden] { display: none; }
		.wpms-setup-wizard-welcome__error { color: #d63638; margin: 0 0 20px; }
		.wpms-setup-wizard-welcome__notice { text-align: center; color: #777777; font-size: 13px; margin: -30px 0 50px; }
	</style>
</head>
<body class="wp-mail-smtp-setup-wizard">
	<div class="wp-mail-smtp-admin-page">
		<div class="wp-mail-smtp-welcome">
			<header class="wp-mail-smtp-setup-wizard-header">
				<h1 class="wp-mail-smtp-setup-wizard-logo">
					<div class="wp-mail-smtp-logo">
						<img class="wp-mail-smtp-logo-img" src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'WP Mail SMTP logo', 'wp-mail-smtp' ); ?>" />
					</div>
				</h1>
			</header>
			<div class="wp-mail-smtp-setup-wizard-container">
				<main class="wp-mail-smtp-setup-wizard-content">
					<div class="wp-mail-smtp-setup-wizard-content-container">
						<div class="wp-mail-smtp-content-header">
							<h2><?php esc_html_e( 'Welcome to the WP Mail SMTP Setup Wizard!', 'wp-mail-smtp' ); ?></h2>
							<p class="subtitle"><?php esc_html_e( 'We\'ll guide you through each step needed to get WP Mail SMTP fully set up on your site.', 'wp-mail-smtp' ); ?></p>
						</div>
						<p id="wp-mail-smtp-setup-wizard-error" class="wpms-setup-wizard-welcome__error" role="alert" hidden></p>
						<button type="button" id="wp-mail-smtp-setup-wizard-start" class="wp-mail-smtp-button wp-mail-smtp-button-main wp-mail-smtp-button-large">
							<span class="text-with-arrow text-with-arrow-right">
								<?php esc_html_e( 'Let\'s Get Started', 'wp-mail-smtp' ); ?>
								<svg class="icon" width="16" height="22" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M340.485 366l99.03-99.029c4.686-4.686 4.686-12.284 0-16.971l-99.03-99.029c-7.56-7.56-20.485-2.206-20.485 8.485v71.03H12c-6.627 0-12 5.373-12 12v32c0 6.627 5.373 12 12 12h308v71.03c0 10.689 12.926 16.043 20.485 8.484z"></path></svg>
							</span>
						</button>
						<noscript>
							<p><?php esc_html_e( 'The setup wizard needs JavaScript. Please enable it and reload this page.', 'wp-mail-smtp' ); ?></p>
						</noscript>
					</div>
				</main>
				<footer>
					<p class="wp-mail-smtp-exit-link">
						<a href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Go back to the Dashboard', 'wp-mail-smtp' ); ?></a>
					</p>
					<?php if ( ! $is_local_environment ) : ?>
						<p class="wpms-setup-wizard-welcome__notice">
							<?php esc_html_e( 'Note: You will be transferred to wpmailsmtp.com to complete the setup wizard.', 'wp-mail-smtp' ); ?>
						</p>
					<?php endif; ?>
				</footer>
			</div>
		</div>
		<div id="wp-mail-smtp-setup-wizard-loading" class="wp-mail-smtp-loading" hidden>
			<img src="<?php echo esc_url( $loading_url ); ?>" class="icon" width="150" height="159" alt="" />
		</div>
	</div>
	<form id="wp-mail-smtp-setup-wizard-handoff" method="POST"></form>
	<script>
		(function () {
			<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded, read as a JavaScript literal. ?>
			var settings = <?php echo $settings; ?>;
			var button = document.getElementById('wp-mail-smtp-setup-wizard-start');
			var error = document.getElementById('wp-mail-smtp-setup-wizard-error');
			var loading = document.getElementById('wp-mail-smtp-setup-wizard-loading');

			function setBusy(busy) {
				loading.hidden = !busy;
				button.disabled = busy;
			}

			function fallback() {
				window.location.replace(settings.fallback_url);
			}

			function handoff(data) {
				var form = document.getElementById('wp-mail-smtp-setup-wizard-handoff');
				var ticket = document.createElement('input');

				ticket.type = 'hidden';
				ticket.name = 'ticket';
				ticket.value = data.ticket;

				// In the body, not the URL: a ticket in a query string would reach
				// the browser history and the hosted wizard's access logs.
				form.appendChild(ticket);
				form.action = settings.handoff_url;
				form.submit();
			}

			function showError(message) {
				error.textContent = message;
				error.hidden = false;
				setBusy(false);
			}

			function start() {
				var request = new XMLHttpRequest();

				setBusy(true);
				error.hidden = true;

				request.open('POST', settings.ajax_url);
				request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
				// Above the handler's own budget for the exchange, so a hung hop is
				// reported by the handler.
				request.timeout = 20000;
				request.onerror = fallback;
				request.ontimeout = fallback;

				request.onload = function () {
					var body = null;

					try {
						body = JSON.parse(request.responseText);
					} catch (e) {
						// A non-JSON body means something answered ahead of the handler:
						// a reachability failure like any other.
					}

					if (body && body.success && body.data && body.data.ticket) {
						handoff(body.data);
						return;
					}

					if (body && body.data && body.data.fallback === false) {
						showError(body.data.message);
						return;
					}

					fallback();
				};

				request.send('action=' + encodeURIComponent(settings.action) + '&nonce=' + encodeURIComponent(settings.nonce));
			}

			button.addEventListener('click', start);

			// A page restored from the back/forward cache comes back mid-handoff.
			window.addEventListener('pageshow', function () {
				setBusy(false);
			});
		}());
	</script>
</body>
</html>
