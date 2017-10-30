=== WP Mail SMTP by WPForms ===
Contributors: wpforms, smub, jaredatch, slaFFik
Donate link: https://wpforms.com/
Tags: mail, smtp, wp_mail, mailer, phpmailer
Requires at least: 2.7
Tested up to: 4.8
Stable tag: trunk

The most popular WordPress SMTP and PHP Mailer plugin. Trusted by over 700k sites.

== Description ==

This plugin reconfigures the wp_mail() function to use SMTP instead of mail() and creates an options page that allows you to specify various options.

You can set the following options:

* Specify the from name and email address for outgoing email.
* Choose to send mail by SMTP or PHP's mail() function.
* Specify an SMTP host (defaults to localhost).
* Specify an SMTP port (defaults to 25).
* Choose SSL / TLS encryption (not the same as STARTTLS).
* Choose to use SMTP authentication or not (defaults to not).
* Specify an SMTP username and password.

WP Mail SMTP plugin works with all major email services such as Gmail, Yahoo, Outlook, Microsoft Live, and any other email sending service that offers SMTP.

= Why WP Mail SMTP =

The goal of WP Mail SMTP plugin is to help fix the common "WordPress not sending an email issue".

Often times an email sent out by your WordPress site either lands in the spam folder or get completely rejected by popular email providers.

SMTP (Simple Mail Transfer Protocol) is the industry standard for sending emails. WP Mail SMTP helps you use proper authentication which increases email deliverability.

= Credits =

WP Mail SMTP plugin was originally created by Callum Macdonald. It is now owned and maintained by the team behind <a href="https://wpforms.com/" rel="friend">WPForms</a> - the best drag & drop form builder for WordPress.

You can try the <a href="https://wordpress.org/plugins/wpforms-lite/" rel="friend">free version of WPForms plugin</a> to see why it's the best in the market.

= What's Next =

If you like this plugin, then consider checking out our other projects:

* <a href="http://optinmonster.com/" rel="friend" title="OptinMonster">OptinMonster</a> - Get More Email Subscribers
* <a href="http://soliloquywp.com/" rel="friend" title="Soliloquy">Soliloquy</a> - Best WordPress Slider Plugin
* <a href="http://enviragallery.com/" rel="friend" title="Envira Gallery">Envira Gallery</a> - Best WordPress Gallery Plugin
* <a href="https://www.monsterinsights.com/" rel="friend" title="MonsterInsights">MonsterInsights</a> - Best Google Analytics Plugin for WordPress

Visit <a href="http://www.wpbeginner.com/" rel="friend" title="WPBeginner">WPBeginner</a> to learn from our <a href="http://www.wpbeginner.com/category/wp-tutorials/" rel="friend" title="WordPress Tutorials">WordPress Tutorials</a> and find out about other <a href="http://www.wpbeginner.com/category/plugins/" rel="friend" title="Best WordPress Plugins">best WordPress plugins</a>.

== Installation ==

1. Install WP Mail SMTP by WPForms either via the WordPress.org plugin repository or by uploading the files to your server. (See instructions on <a href="http://www.wpbeginner.com/beginners-guide/step-by-step-guide-to-install-a-wordpress-plugin-for-beginners/" rel="friend">how to install a WordPress plugin</a>)
2. Activate WP Mail SMTP by WPForms.
3. Navigate to the Settings area of WP Mail SMTP in the WordPress admin.
4. Want to support us? Consider trying <a href="https://wpforms.com/?utm_source=wprepo&utm_medium=link&utm_campaign=liteversion" rel="friend" title="WPForms">WPForms Pro</a> - the best WordPress contact form plugin!

== Frequently Asked Questions ==

= My plugin still sends mail via the mail() function =

If other plugins you're using are not coded to use the wp_mail() function but instead call PHP's mail() function directly, they will bypass the settings of this plugin. Normally, you can edit the other plugins and simply replace the `mail(` calls with `wp_mail(` (just adding wp_ in front) and this will work. I've tested this on a couple of plugins and it works, but it may not work on all plugins.

= Will this plugin work with WordPress versions less than 2.7? =

No. WordPress 2.7 changed the way options were updated, so the options page will only work on 2.7 or later.

= Can I use this plugin to send email via Gmail / Google Apps =

Yes. Use these settings:
Mailer: SMTP
SMTP Host: smtp.gmail.com
SMTP Port: 465
Encryption: SSL
Authentication: Yes
Username: your full gmail address
Password: your mail password

= Can you add feature x, y or z to the plugin? =

Short answer: maybe.

By all means please contact us to discuss features or options you'd like to see added to the plugin. We can't guarantee to add all of them, but we will consider all sensible requests. We can be contacted here:
<a href="https://wpforms.com/contact/" rel="friend">https://wpforms.com/contact/</a>

== Screenshots ==

1. Advanced Email Options
2. SMTP Options
3. Pepipost SMTP Options
4. Send a Test Email

== Changelog ==

= [0.11.1] - 2017-10-30 =
* Older PHP compatibility fix.

= [0.11] - 2017-10-30 =
* Added: Helper description to Return Path option.
* Added: Filter `wp_mail_smtp_admin_test_email_smtp_debug` to increase the debug message verbosity.
* Added: PHP 5.2 notice.
* Changed: Localization fixes, proper locale name.
* Changed: Code style improvements and optimizations for both HTML and PHP.
* Changed: Inputs for emails now have a proper type `email`, instead of a generic `text`.
* Changed: Turn off `$phpmailer->SMTPAutoTLS` when `No encryption` option is set to prevent error while sending emails.
* Changed: Hide Pepipost for those who are not using it.
* Changed: WP CLI support improved.

= 0.10.1 =
* Addition of Pepipost and cleanup of admin page.

= 0.10.0 =
* Addition of Pepipost and cleanup of admin page.

= 0.9.6 =
* Minor security fix, sanitize test email address.

= 0.9.5 =
* Minor security fix, hat tip JD Grimes.

= 0.9.4 =
* Improvement to the test email function, very low priority update.

= 0.9.3 =
* Fixing reported issue with passing by reference. props Adam Conway

= 0.9.2 =
* Removing the deprecation notice.

= 0.9.1 =
* $phpmailer->language became protected in WP 3.2, no longer unset on debug output.

= 0.9.0 =
* Typo in the From email description.
* Removed changelog from plugin file, no need to duplicate it.
* Optionally set $phpmailer->Sender from from email, helps with sendmail / mail().

= 0.8.7 =
* Fix for a long standing bug that caused an error during plugin activation.

= 0.8.6 =
* The Settings link really does work this time, promise. Apologies for the unnecessary updates.

= 0.8.5 =
* Bugfix, the settings link on the Plugin page was broken by 0.8.4.

= 0.8.4 =
* Minor bugfix, remove use of esc_html() to improve backwards compatibility.
* Removed second options page menu props ovidiu.

= 0.8.3 =
* Bugfix, return WPMS_MAIL_FROM_NAME, props nacin.
* Add Settings link, props Mike Challis http://profiles.wordpress.org/MikeChallis/

= 0.8.2 =
* Bugfix, call phpmailer_init_smtp() correctly, props Sinklar.

= 0.8.1 =
* Internationalisation improvements.

= 0.8 =
* Added port, SSL/TLS, option whitelisting, validate_email(), and constant options.

= 0.7 =
* Added checks to only override the default from name / email

= 0.6 =
* Added additional SMTP debugging output

= 0.5.2 =
* Fixed a pre 2.3 bug to do with mail from

= 0.5.1 =
* Added a check to display a warning on versions prior to 2.3

= 0.5.0 =
* Upgraded to match 2.3 filters which add a second filter for from name

= 0.4.2 =
* Fixed a bug in 0.4.1 and added more debugging output

= 0.4.1 =
* Added $phpmailer->ErroInfo to the test mail output

= 0.4 =
* Added the test email feature and cleaned up some other bits and pieces

= 0.3.2 =
* Changed to use register_activation_hook for greater compatability

= 0.3.1 =
* Added readme for WP-Plugins.org compatability

= 0.3 =
* Various bugfixes and added From options

= 0.2 =
* Reworked approach as suggested by westi, added options page

= 0.1 =
* Initial approach, copying the wp_mail function and replacing it

== Upgrade Notice ==

= 0.10.1 =
Addition of Pepipost and cleanup of admin page.

= 0.10.0 =
Addition of Pepipost and cleanup of admin page.

= 0.9.6 =
Minor security fix, sanitize test email address.

= 0.9.5 =
Minor security fix, hat tip JD Grimes.

= 0.9.4 =
Improvement to the test email function, very low priority update.

= 0.9.3 =
Fixing reported issue with passing by reference.

= 0.9.2 =
Removing the deprecation notice.

= 0.9.1 =
Test mail functionality was broken on upgrade to 3.2, now restored.

= 0.9.0 =
Low priority upgrade. Improves the appearance of the options page.

= 0.8.7 =
Very low priority update. Fixes a bug that causes a spurious error during activation.

= 0.8.6 =
Low priority update. The Settings link was still broken in 0.8.5.

= 0.8.5 =
Minor bugfix correcting the Settings link bug introduced in 0.8.4. Very low priority update.

= 0.8.4 =
Minor bugfix for users using constants. Another very low priority upgrade. Apologies for the version creep.

= 0.8.3 =
Minor bugfix for users using constants. Very low priority upgrade.
