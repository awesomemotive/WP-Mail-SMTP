<?php
/*
Plugin Name: WP-Mail-SMTP
Version: 0.3.2
Plugin URI: http://www.callum-macdonald.com/code/wp-mail-smtp/
Description: Reconfigures the wp_mail() function to use SMTP instead of mail() and creates an options page to manage host, username, password, etc.
Author: Callum Macdonald
Author URI: http://www.callum-macdonald.com/
*/

/**
 * @author Callum Macdonald
 * @copyright Callum Macdonald, 2007, All Rights Reserved
 * This code is released under the GPL licence version 3 or later, available here
 * http://www.gnu.org/licenses/gpl.txt
 */

/**
 * TODO
 * 
 * + Improve email checks for mail_from
 * 
 * CHANGELOG
 * 
 * 0.3.2 - Changed to use register_activation_hook for greater compatability
 * 0.3.1 - Added readme for WP-Plugins.org compatability
 * 0.3 - Various bugfixes and added From options
 * 0.2 - Reworked approach as suggested by westi, added options page
 * 0.1 - Initial approach, copying the wp_mail function and replacing it
 */

// Array of options and their default values
$wpms_options = array (
	'mail_from' => '',
	'mailer' => 'smtp',
	'smtp_host' => 'localhost',
	'smtp_auth' => 'false',
	'smtp_user' => '',
	'smtp_pass' => '',
);

/**
 * Activation function. This function creates the required options and defaults.
 */
if (!function_exists('wp_mail_smtp_activate')) {
	
	function wp_mail_smtp_activate() {
		
		global $wpms_options;
		
		// Create the required options...
		foreach ($wpms_options as $name => $val) {
			add_option($name,$val);
		}
		
	}
	
}

// To avoid any (very unlikely) clashes, check if the function alredy exists
if (!function_exists('phpmailer_init_smtp')) {
	
	// This code is copied, from wp-includes/pluggable.php as at version 2.2.2
	function phpmailer_init_smtp($phpmailer) {
		
		// Are at least the mailer and host set and non-blank?
		if (!is_string(get_option('mailer')) || get_option('mailer') == '' || !is_string(get_option('smtp_host')) || get_option('smtp_host') == "" ) {
			return;
		}
		
		// Set the mailer type as per config above, this overrides the already called isMail method
		$phpmailer->Mailer = get_option('mailer');
		
		// If we're sending via SMTP, set the host
		if (get_option('mailer') == "smtp") {
			$phpmailer->Host = get_option('smtp_host');
			// If we're using smtp auth, set the username & password
			if (get_option('smtp_auth') == "true") {
				$phpmailer->SMTPAuth = TRUE;
				$phpmailer->Username = get_option('smtp_user');
				$phpmailer->Password = get_option('smtp_pass');
			}
		}
		
		// You can add your own options here, see the phpmailer documentation for more info:
		// http://phpmailer.sourceforge.net/docs/
		
		// Stop adding options here.
		
	} // End of phpmailer_init_smtp() function definition
	
}


/**
 * This function outputs the plugin options page.
 */
if (!function_exists('wp_mail_smtp_options_page')) {
	
	// Define the function
	function wp_mail_smtp_options_page() {
		
		// Load the options
		global $wpms_options;
		
		?>
<div class="wrap">
<h2><?php _e('Advanced Email Options') ?></h2>
<form method="post" action="options.php">
<?php wp_nonce_field('update-options') ?>
<p class="submit"><input type="submit" name="Submit" value="<?php _e('Update Options &raquo;') ?>" />
<fieldset class="options">
<legend>From</legend>
<table class="optiontable">
<tr valign="top">
<th scope="row"><?php _e('From:') ?> </th>
<td><p><input name="mail_from" type="text" id="mail_from" value="<?php print(get_option('mail_from')); ?>" size="40" class="code" /><br />
<?php _e('You can specify just an email address or a name and email address in the form &quot;Name&lt;email@example.com&gt;&quot;. If this is left blank or does not contain an @ symbol, the admin email will be used.'); ?></p></td>
</tr>
</table>

<legend>Mailer</legend>
<table class="optiontable">
<tr valign="top">
<th scope="row"><?php _e('Mailer:') ?> </th>
<td>
<p><input id="mailer_smtp" type="radio" name="mailer" value="smtp" <?php checked('smtp', get_option('mailer')); ?> />
<label for="mailer_smtp"><?php _e('Send all WordPress emails via SMTP.'); ?></label></p>
<p><input id="mailer_mail" type="radio" name="mailer" value="mail" <?php checked('mail', get_option('mailer')); ?> />
<label for="mailer_mail"><?php _e('Use the PHP mail() function to send emails.'); ?></label></p>
</td>
</tr>
</table>

<legend>SMTP Options</legend>
<p><?php _e('These options only apply if you have chosen to send mail by SMTP above.'); ?></p>
<table class="optiontable">
<tr valign="top">
<th scope="row"><?php _e('SMTP Host:') ?> </th>
<td><input name="smtp_host" type="text" id="smtp_host" value="<?php print(get_option('smtp_host')); ?>" size="40" class="code" /></td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('Authentication:') ?> </th>
<td>
<p><input id="smtp_auth_false" type="radio" name="smtp_auth" value="false" <?php checked('false', get_option('smtp_auth')); ?> />
<label for="smtp_auth_false"><?php _e('No: Do not use SMTP authentication.'); ?></label></p>
<p><input id="smtp_auth_true" type="radio" name="smtp_auth" value="true" <?php checked('true', get_option('smtp_auth')); ?> />
<label for="smtp_auth_true"><?php _e('Yes: Use SMTP authentication.'); ?></label></p>
<p><?php _e('If this is set to no, the values below are ignored.'); ?></p>
</td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('Username:') ?> </th>
<td><input name="smtp_user" type="text" id="smtp_user" value="<?php print(get_option('smtp_user')); ?>" size="40" class="code" /></td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('Password:') ?> </th>
<td><input name="smtp_pass" type="text" id="smtp_pass" value="<?php print(get_option('smtp_pass')); ?>" size="40" class="code" /></td>
</tr>
</table>
</fieldset>

<p class="submit"><input type="submit" name="Submit" value="<?php _e('Update Options &raquo;') ?>" />
<input type="hidden" name="action" value="update" />
</p>
<input type="hidden" name="page_options" value="<?php print(implode(',',array_keys($wpms_options))); ?>">
</form>

</div>
		<?php
		
	} // End of wp_mail_smtp_options_page() function definition
	
}

/**
 * This function adds the required page (only 1 at the moment).
 */
if (!function_exists('wp_mail_smtp_menus')) {
	
	function wp_mail_smtp_menus() {
		
		if (function_exists('add_submenu_page')) {
			add_options_page(__('Advanced Email Options'),__('Email'),'manage_options',__FILE__,'wp_mail_smtp_options_page');
		}
		
	} // End of wp_mail_smtp_menus() function definition
	
}

/**
 * This function sets who the mail is from
 */
if (!function_exists('wp_mail_smtp_mail_from')) {
	
	function wp_mail_smtp_mail_from ($orig) {
		
		/**
		 * //// CHMAC TODO
		 * This needs reworked, is_email only checks the email part, so before
		 * using it, we'll need to strip the name out. Maybe it's quicker to
		 * copy the checks from is_email...
		 */
		
		// If we can, use the is_email function to verify the email
		if ( function_exists('is_email') ) {
			if ( is_email( get_option('mail_from') ) ) {
				return(get_option('mail_from'));
			}
			else {
				return (get_option('admin_email'));
			}
		}
		// If is_email is not available, check there's an @ symbol
		elseif (strpos(get_option('mail_from'),'@')) {
			return(get_option('mail_from'));
		}
		// If there's no is_email and no @, use the admin email instead
		else {
			return(get_option('admin_email'));
		}
		
	} // End of wp_mail_smtp_mail_from() function definition
	
}

// Add an action on phpmailer_init
add_action('phpmailer_init','phpmailer_init_smtp');
// Add the create pages options
add_action('admin_menu','wp_mail_smtp_menus');
// Add an activation hook for this plugin
register_activation_hook(__FILE__,'wp_mail_smtp_activate');
// Add a filter to replace the mail from address
add_filter('wp_mail_from','wp_mail_smtp_mail_from');

?>