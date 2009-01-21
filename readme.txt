=== WP Mail SMTP ===
Contributors: chmac
Donate link: http://www.callum-macdonald.com/code/donate/
Tags: mail, smtp, wp_mail, mailer, phpmailer
Requires at least: 2.7
Tested up to: 2.7
Stable tag: 0.8.2

Reconfigures the wp_mail() function to use SMTP instead of mail() and creates an options page to manage the settings.

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

== Installation ==

1. Download
2. Upload to your `/wp-contents/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.

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

By all means please contact me to discuss features or options you'd like to see added to the plugin. I can't guarantee to add all of them, but I will consider all sensible requests. I can be contacted here:
<http://www.callum-macdonald.com/contact/>

== Screenshots ==

1. Screenshot of the Options > Email panel.

== Support Questions ==

If you have support questions not covered in this readme, you can contact me here:
<http://www.callum-macdonald.com/contact/>
