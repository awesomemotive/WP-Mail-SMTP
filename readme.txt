=== WP Mail SMTP ===
Contributors: chmac
Donate link: http://www.callum-macdonald.com/code/donate/
Tags: mail, smtp, wp_mail, mailer, phpmailer
Requires at least: 2.3
Tested up to: 2.3
Stable tag: 0.5

Reconfigures the wp_mail() function to use SMTP instead of mail() and creates an options page to manage host, username, password, etc.

== Description ==

This plugin reconfigures the wp_mail() function to use SMTP instead of mail() and creates an options page that allows you to specify various options.

You can set the following options:
* Specify the from name and email address for outgoing email.
* Choose to send mail by SMTP or PHP's mail() function.
* Specify an SMTP host (defaults to localhost).
* Choose to use SMTP authentication or not (defaults to not).
* Specify an SMTP username and password.

== Installation ==

1. Download
2. Upload to your `/wp-contents/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= My plugin still sends mail via the mail() function =

If other plugins you're using are not coded to use the wp_mail() function but instead call PHP's mail() function directly, they will bypass the settings of this plugin. Normally, you can edit the other plugins and simply replace the `mail(` calls with `wp_mail(` (just adding wp_ in front) and this will work. I've tested this on a couple of plugins and it works, but it may not work on all plugins.

= Can I specify an SMTP port number / other setting? =

The simple answer is no.

However, you're welcome to edit the code of this plugin and add your options directly to the code. See the wp_mail_smtp.php file at line 88. For more information on the options you can add, see the PHP Mailer documentation here:
<http://phpmailer.sourceforge.net/docs/>

= Can you add feature x, y or z to the plugin? =

Short answer: maybe.

By all means please contact me to discuss features or options you'd like to see added to the plugin. I can't guarantee to add all of them, but I will consider all sensible requests. I can be contacted here:
<http://www.callum-macdonald.com/contact/>

= Will this plugin work with WordPress version < 2.3? =

Short answer: no.

WordPress version 2.3 introduced two new filters for mail_from and mail_from_name, which allow you to set the two variables separately. Previously, both were set in one filter. From version 0.5 this change was introduced, so the plugin will not work with versions prevoius to 2.3. However, you could use version 4.2 of this plugin instead, that should work with earlier versions of WordPress.

== Screenshots ==

1. Screenshot of the Options > Email panel.

== Support Questions ==

If you have support questions not covered in this readme, you can contact me here:
<http://www.callum-macdonald.com/contact/>
