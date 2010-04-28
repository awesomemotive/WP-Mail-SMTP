<?php
/*
Plugin Name: WP-Mail-SMTP
Version: 0.8.6
Plugin URI: http://www.callum-macdonald.com/code/wp-mail-smtp/
Description: Reconfigures the wp_mail() function to use SMTP instead of mail() and creates an options page to manage the settings.
Author: Callum Macdonald
Author URI: http://www.callum-macdonald.com/
*/

/**
 * @author Callum Macdonald
 * @copyright Callum Macdonald, 2007-8, All Rights Reserved
 * This code is released under the GPL licence version 3 or later, available here
 * http://www.gnu.org/licenses/gpl.txt
 */

/**
 * Setting options in wp-config.php
 * 
 * Specifically aimed at WPMU users, you can set the options for this plugin as
 * constants in wp-config.php. This disables the plugin's admin page and may
 * improve performance very slightly. Copy the code below into wp-config.php.
 */
/*
define('WPMS_ON', true);
define('WPMS_MAIL_FROM', 'From Email');
define('WPMS_MAIL_FROM_NAME', 'From Name');
define('WPMS_MAILER', 'smtp'); // Possible values 'smtp', 'mail', or 'sendmail'
define('WPMS_SMTP_HOST', 'localhost'); // The SMTP mail host
define('WPMS_SMTP_PORT', 25); // The SMTP server port number
define('WPMS_SSL', ''); // Possible values '', 'ssl', 'tls' - note TLS is not STARTTLS
define('WPMS_SMTP_AUTH', true); // True turns on SMTP authentication, false turns it off
define('WPMS_SMTP_USER', 'username'); // SMTP authentication username, only used if WPMS_SMTP_AUTH is true
define('WPMS_SMTP_PASS', 'password'); // SMTP authentication password, only used if WPMS_SMTP_AUTH is true
*/

/**
 * CHANGELOG
 * 
 * 0.8.6 - The Settings link really does work this time, promise. Apologies for the unnecessary updates.
 * 0.8.5 - Bugfix, the settings link on the Plugin page was broken by 0.8.4.
 * 0.8.4 - Minor bugfix, remove use of esc_html() to improve backwards compatibility. Removed second options page menu props ovidiu.
 * 0.8.3 - Bugfix, return WPMS_MAIL_FROM_NAME, props nacin. Add Settings link, props MikeChallis.
 * 0.8.2 - Bugfix, call phpmailer_init_smtp() correctly, props Sinklar.
 * 0.8.1 - Internationalisation improvements.
 * 0.8 - Added port, SSL/TLS, option whitelisting, validate_email(), and constant options.
 * 0.7 - Added checks to only override the default from name / email
 * 0.6 - Added additional SMTP debugging output
 * 0.5.2 - Fixed a pre 2.3 bug to do with mail from
 * 0.5.1 - Added a check to display a warning on versions prior to 2.3
 * 0.5.0 - Upgraded to match 2.3 filters which add a second filter for from name
 * 0.4.2 - Fixed a bug in 0.4.1 and added more debugging output
 * 0.4.1 - Added $phpmailer->ErroInfo to the test mail output
 * 0.4 - Added the test email feature and cleaned up some other bits and pieces
 * 0.3.2 - Changed to use register_activation_hook for greater compatability
 * 0.3.1 - Added readme for WP-Plugins.org compatability
 * 0.3 - Various bugfixes and added From options
 * 0.2 - Reworked approach as suggested by westi, added options page
 * 0.1 - Initial approach, copying the wp_mail function and replacing it
 */

// Array of options and their default values
$wpms_options = array (
	'mail_from' => '',
	'mail_from_name' => '',
	'mailer' => 'smtp',
	'smtp_host' => 'localhost',
	'smtp_port' => '25',
	'smtp_ssl' => 'none',
	'smtp_auth' => false,
	'smtp_user' => '',
	'smtp_pass' => ''
);


/**
 * Activation function. This function creates the required options and defaults.
 */
if (!function_exists('wp_mail_smtp_activate')) :
function wp_mail_smtp_activate() {
	
	global $wpms_options;
	
	// Create the required options...
	foreach ($wpms_options as $name => $val) {
		add_option($name,$val);
	}
	
}
endif;

if (!function_exists('wp_mail_smtp_whitelist_options')) :
function wp_mail_smtp_whitelist_options($whitelist_options) {
	
	global $wpms_options;
	
	// Add our options to the array
	$whitelist_options['email'] = array_keys($wpms_options);
	
	return $whitelist_options;
	
}
endif;

// To avoid any (very unlikely) clashes, check if the function alredy exists
if (!function_exists('phpmailer_init_smtp')) :
// This code is copied, from wp-includes/pluggable.php as at version 2.2.2
function phpmailer_init_smtp($phpmailer) {
	
	// If constants are defined, apply those options
	if (defined('WPMS_ON') && WPMS_ON) {
		
		$phpmailer->Mailer = WPMS_MAILER;
		
		if (WPMS_MAILER == 'smtp') {
			$phpmailer->SMTPSecure = WPMS_SSL;
			$phpmailer->Host = WPMS_SMTP_HOST;
			$phpmailer->Port = WPMS_SMTP_PORT;
			if (WPMS_SMTP_AUTH) {
				$phpmailer->SMTPAuth = true;
				$phpmailer->Username = WPMS_SMTP_USER;
				$phpmailer->Password = WPMS_SMTP_PASS;
			}
		}
		
		// If you're using contstants, set any custom options here
		
	}
	else {
		
		// Check that mailer is not blank, and if mailer=smtp, host is not blank
		if ( ! get_option('mailer') || ( get_option('mailer') == 'smtp' && ! get_option('smtp_host') ) ) {
			return;
		}
		
		// Set the mailer type as per config above, this overrides the already called isMail method
		$phpmailer->Mailer = get_option('mailer');
		
		// Set the SMTPSecure value, if set to none, leave this blank
		$phpmailer->SMTPSecure = get_option('smtp_ssl') == 'none' ? '' : get_option('smtp_ssl');
		
		// If we're sending via SMTP, set the host
		if (get_option('mailer') == "smtp") {
			
			// Set the SMTPSecure value, if set to none, leave this blank
			$phpmailer->SMTPSecure = get_option('smtp_ssl') == 'none' ? '' : get_option('smtp_ssl');
			
			// Set the other options
			$phpmailer->Host = get_option('smtp_host');
			$phpmailer->Port = get_option('smtp_port');
			
			// If we're using smtp auth, set the username & password
			if (get_option('smtp_auth') == "true") {
				$phpmailer->SMTPAuth = TRUE;
				$phpmailer->Username = get_option('smtp_user');
				$phpmailer->Password = get_option('smtp_pass');
			}
		}
		
		// You can add your own options here, see the phpmailer documentation for more info:
		// http://phpmailer.sourceforge.net/docs/
		
		
		
		// STOP adding options here.
		
	}
	
} // End of phpmailer_init_smtp() function definition
endif;



/**
 * This function outputs the plugin options page.
 */
if (!function_exists('wp_mail_smtp_options_page')) :
// Define the function
function wp_mail_smtp_options_page() {
	
	// Load the options
	global $wpms_options, $phpmailer;
	
	// Make sure the PHPMailer class has been instantiated 
	// (copied verbatim from wp-includes/pluggable.php)
	// (Re)create it, if it's gone missing
	if ( !is_object( $phpmailer ) || !is_a( $phpmailer, 'PHPMailer' ) ) {
		require_once ABSPATH . WPINC . '/class-phpmailer.php';
		require_once ABSPATH . WPINC . '/class-smtp.php';
		$phpmailer = new PHPMailer();
	}

	// Send a test mail if necessary
	if (isset($_POST['wpms_action']) && $_POST['wpms_action'] == __('Send Test', 'wp_mail_smtp') && isset($_POST['to'])) {
		
		// Set up the mail variables
		$to = $_POST['to'];
		$subject = 'WP Mail SMTP: ' . __('Test mail to ', 'wp_mail_smtp') . $to;
		$message = __('This is a test email generated by the WP Mail SMTP WordPress plugin.', 'wp_mail_smtp');
		
		// Set SMTPDebug to level 2
		$phpmailer->SMTPDebug = 2;
		
		// Start output buffering to grab smtp debugging output
		ob_start();

		// Send the test mail
		$result = wp_mail($to,$subject,$message);
		
		// Grab the smtp debugging output
		$smtp_debug = ob_get_clean();
		
		// Output the response
		?>
<div id="message" class="updated fade"><p><strong><?php _e('Test Message Sent', 'wp_mail_smtp'); ?></strong></p>
<p><?php _e('The result was:', 'wp_mail_smtp'); ?></p>
<pre><?php var_dump($result); ?></pre>
<?php if ($result != true) { ?>
<p><?php _e('The full debugging output is shown below:', 'wp_mail_smtp'); ?></p>
<pre><?php var_dump($phpmailer); ?></pre>
<?php } ?>
<p><?php _e('The SMTP debugging output is shown below:', 'wp_mail_smtp'); ?></p>
<pre><?php echo $smtp_debug ?></pre>
</div>
		<?php

	}
	
	?>
<div class="wrap">
<h2><?php _e('Advanced Email Options', 'wp_mail_smtp'); ?></h2>
<form method="post" action="options.php">
<?php wp_nonce_field('email-options'); ?>
<p class="submit"><input type="submit" name="Submit" value="<?php _e('Update Options &raquo;', 'wp_mail_smtp'); ?>" />
<fieldset class="options">
<legend><?php _e('From', 'wp_mail_smtp'); ?></legend>
<table class="optiontable">
<tr valign="top">
<th scope="row"><?php _e('From Email:', 'wp_mail_smtp'); ?> </th>
<td><p><input name="mail_from" type="text" id="mail_from" value="<?php print(get_option('mail_from')); ?>" size="40" class="code" /><br />
<?php _e('You can specify the email address that emails should be sent from. If you leave this blank, the admin email will be used.', 'wp_mail_smtp'); if(get_option('db_version') < 6124) { print('<br /><span style="color: red;">'); _e('<strong>Please Note:</strong> You appear to be using a version of WordPress prior to 2.3. Please ignore the From Name field and instead enter Name&lt;email@domain.com&gt; in this field.', 'wp_mail_smtp'); print('</span>'); } ?></p></td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('From Name:', 'wp_mail_smtp'); ?> </th>
<td><p><input name="mail_from_name" type="text" id="mail_from_name" value="<?php print(get_option('mail_from_name')); ?>" size="40" class="code" /><br />
<?php _e('You can specify the name that emails should be sent from. If you leave this blank, the emails will be sent from WordPress.', 'wp_mail_smtp'); ?></p></td>
</tr>
</table>

<legend><?php _e('Mailer', 'wp_mail_smtp'); ?></legend>
<table class="optiontable">
<tr valign="top">
<th scope="row"><?php _e('Mailer:', 'wp_mail_smtp'); ?> </th>
<td>
<p><input id="mailer_smtp" type="radio" name="mailer" value="smtp" <?php checked('smtp', get_option('mailer')); ?> />
<label for="mailer_smtp"><?php _e('Send all WordPress emails via SMTP.', 'wp_mail_smtp'); ?></label></p>
<p><input id="mailer_mail" type="radio" name="mailer" value="mail" <?php checked('mail', get_option('mailer')); ?> />
<label for="mailer_mail"><?php _e('Use the PHP mail() function to send emails.', 'wp_mail_smtp'); ?></label></p>
</td>
</tr>
</table>

<legend><?php _e('SMTP Options', 'wp_mail_smtp'); ?></legend>
<p><?php _e('These options only apply if you have chosen to send mail by SMTP above.', 'wp_mail_smtp'); ?></p>
<table class="optiontable">
<tr valign="top">
<th scope="row"><?php _e('SMTP Host:', 'wp_mail_smtp'); ?> </th>
<td><input name="smtp_host" type="text" id="smtp_host" value="<?php print(get_option('smtp_host')); ?>" size="40" class="code" /></td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('SMTP Port:', 'wp_mail_smtp'); ?> </th>
<td><input name="smtp_port" type="text" id="smtp_port" value="<?php print(get_option('smtp_port')); ?>" size="6" class="code" /></td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('Encryption:', 'wp_mail_smtp'); ?> </th>
<td>
<p><input id="smtp_ssl_none" type="radio" name="smtp_ssl" value="none" <?php checked('none', get_option('smtp_ssl')); ?> />
<label for="smtp_ssl_none"><?php _e('No encryption.', 'wp_mail_smtp'); ?></label></p>
<p><input id="smtp_ssl_ssl" type="radio" name="smtp_ssl" value="ssl" <?php checked('ssl', get_option('smtp_ssl')); ?> />
<label for="smtp_ssl_ssl"><?php _e('Use SSL encryption.', 'wp_mail_smtp'); ?></label></p>
<p><input id="smtp_ssl_tls" type="radio" name="smtp_ssl" value="tls" <?php checked('tls', get_option('smtp_ssl')); ?> />
<label for="smtp_ssl_tls"><?php _e('Use TLS encryption. This is not the same as STARTTLS. For most servers SSL is the recommended option.', 'wp_mail_smtp'); ?></label></p>
</td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('Authentication:', 'wp_mail_smtp'); ?> </th>
<td>
<p><input id="smtp_auth_false" type="radio" name="smtp_auth" value="false" <?php checked('false', get_option('smtp_auth')); ?> />
<label for="smtp_auth_false"><?php _e('No: Do not use SMTP authentication.', 'wp_mail_smtp'); ?></label></p>
<p><input id="smtp_auth_true" type="radio" name="smtp_auth" value="true" <?php checked('true', get_option('smtp_auth')); ?> />
<label for="smtp_auth_true"><?php _e('Yes: Use SMTP authentication.', 'wp_mail_smtp'); ?></label></p>
<p><?php _e('If this is set to no, the values below are ignored.', 'wp_mail_smtp'); ?></p>
</td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('Username:', 'wp_mail_smtp'); ?> </th>
<td><input name="smtp_user" type="text" id="smtp_user" value="<?php print(get_option('smtp_user')); ?>" size="40" class="code" /></td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('Password:', 'wp_mail_smtp'); ?> </th>
<td><input name="smtp_pass" type="text" id="smtp_pass" value="<?php print(get_option('smtp_pass')); ?>" size="40" class="code" /></td>
</tr>
</table>

<p class="submit"><input type="submit" name="Submit" value="<?php _e('Update Options &raquo;', 'wp_mail_smtp'); ?>" />
<input type="hidden" name="action" value="update" />
</p>
<input type="hidden" name="option_page" value="email">
</fieldset>
</form>

<form method="POST">
<fieldset class="options">
<legend><?php _e('Send a Test Email', 'wp_mail_smtp'); ?></legend>
<table class="optiontable">
<tr valign="top">
<th scope="row"><?php _e('To:', 'wp_mail_smtp'); ?> </th>
<td><p><input name="to" type="text" id="to" value="" size="40" class="code" /><br />
<?php _e('Type an email address here and then click Send Test to generate a test email.', 'wp_mail_smtp'); ?></p></td>
</tr>
</table>
<p class="submit"><input type="submit" name="wpms_action" value="<?php _e('Send Test', 'wp_mail_smtp'); ?>" /></p>
</fieldset>
</form>

</div>
	<?php
	
} // End of wp_mail_smtp_options_page() function definition
endif;


/**
 * This function adds the required page (only 1 at the moment).
 */
if (!function_exists('wp_mail_smtp_menus')) :
function wp_mail_smtp_menus() {
	
	if (function_exists('add_submenu_page')) {
		add_options_page(__('Advanced Email Options', 'wp_mail_smtp'),__('Email', 'wp_mail_smtp'),'manage_options',__FILE__,'wp_mail_smtp_options_page');
	}
	
} // End of wp_mail_smtp_menus() function definition
endif;


/**
 * This is copied directly from WPMU wp-includes/wpmu-functions.php
 */
if (!function_exists('validate_email')) :
function validate_email( $email, $check_domain = true) {
    if (ereg('^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+'.'@'.
        '[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.'.
        '[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$', $email))
    {
        if ($check_domain && function_exists('checkdnsrr')) {
            list (, $domain)  = explode('@', $email);

            if (checkdnsrr($domain.'.', 'MX') || checkdnsrr($domain.'.', 'A')) {
                return true;
            }
            return false;
        }
        return true;
    }
    return false;
} // End of validate_email() function definition
endif;


/**
 * This function sets the from email value
 */
if (!function_exists('wp_mail_smtp_mail_from')) :
function wp_mail_smtp_mail_from ($orig) {
	
	// This is copied from pluggable.php lines 348-354 as at revision 10150
	// http://trac.wordpress.org/browser/branches/2.7/wp-includes/pluggable.php#L348
	
	// Get the site domain and get rid of www.
	$sitename = strtolower( $_SERVER['SERVER_NAME'] );
	if ( substr( $sitename, 0, 4 ) == 'www.' ) {
		$sitename = substr( $sitename, 4 );
	}

	$default_from = 'wordpress@' . $sitename;
	// End of copied code
	
	// If the from email is not the default, return it unchanged
	if ( $orig != $default_from ) {
		return $orig;
	}
	
	if (defined('WPMS_ON') && WPMS_ON)
		return WPMS_MAIL_FROM;
	elseif (validate_email(get_option('mail_from'), false))
		return get_option('mail_from');
	
	// If in doubt, return the original value
	return $orig;
	
} // End of wp_mail_smtp_mail_from() function definition
endif;


/**
 * This function sets the from name value
 */
if (!function_exists('wp_mail_smtp_mail_from_name')) :
function wp_mail_smtp_mail_from_name ($orig) {
	
	// Only filter if the from name is the default
	if ($orig == 'WordPress') {
		if (defined('WPMS_ON') && WPMS_ON)
			return WPMS_MAIL_FROM_NAME;
		elseif ( get_option('mail_from_name') != "" && is_string(get_option('mail_from_name')) )
			return get_option('mail_from_name');
	}
	
	// If in doubt, return the original value
	return $orig;
	
} // End of wp_mail_smtp_mail_from_name() function definition
endif;

function wp_mail_plugin_action_links( $links, $file ) {
	if ( $file != plugin_basename( __FILE__ ))
		return $links;

	$settings_link = '<a href="options-general.php?page=wp-mail-smtp/wp_mail_smtp.php">' . __( 'Settings', 'wp_mail_smtp' ) . '</a>';

	array_unshift( $links, $settings_link );

	return $links;
}

// Add an action on phpmailer_init
add_action('phpmailer_init','phpmailer_init_smtp');

if (!defined('WPMS_ON') || !WPMS_ON) {
	// Whitelist our options
	add_filter('whitelist_options', 'wp_mail_smtp_whitelist_options');
	// Add the create pages options
	add_action('admin_menu','wp_mail_smtp_menus');
	// Add an activation hook for this plugin
	register_activation_hook(__FILE__,'wp_mail_smtp_activate');
	// Adds "Settings" link to the plugin action page
	add_filter( 'plugin_action_links', 'wp_mail_plugin_action_links',10,2);
}

// Add filters to replace the mail from name and emailaddress
add_filter('wp_mail_from','wp_mail_smtp_mail_from');
add_filter('wp_mail_from_name','wp_mail_smtp_mail_from_name');

load_plugin_textdomain('wp_mail_smtp', false, dirname(plugin_basename(__FILE__)) . '/langs');

?>
