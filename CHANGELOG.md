# Changelog

All notable changes to this project will be documented in this file.

## [1.3.2] - 2018-06-29
### Fixed: 
- Make sure that other plugins/themes are not conflicting with our TGMPA library.

## [1.3.1] - 2018-06-29
### Fixed: 
- Other SMTP: Clear new Debug messages about failed email delivery on next successful email sending.
- Introduce conditional autoloader to workaround Gmail PHP 5.5 requirement and its library compatibility issues vs PHP 5.3+ minimum viable plugin version.

## [1.3.0] - 2018-06-28
### Added:
- New option: force From Email rewrite regardless of the current value.
- New option: force From Name rewrite regardless of the current value.
- New option: remove all plugin data on plugin uninstall (when user deletes it).
- Notify site admins in wp-admin area with a notice about last failed email delivery. Cleans up on successful delivery.
- Notify site admins in wp-admin area with a notice about possible compatibility issues with other SMTP and email delivery plugins.
- Improve User Debug Experience when doing Email Test - display helpful description and steps to fix the issue.
- New users: provide default SMTP Port value for new users based on Encryption selection.
- New users: notify about not configured plugin settings.
- New users: Recommend free WPForms Lite plugin for those who don't have it.
- SendGrid/Mailgun: provide support for multipart/alternative types of emails.
- Gmail: new button to remove connection and to connect a new Google account.

### Fixed:
- Support plugin installation into /mu-plugins/ directory.
- SendGrid: required text/plain part of email being the first one - fixes plain text emails not having links.
- SendGrid and Mailgun: improperly sending plain text emails in html format.
- SMTP Debug output was empty in some cases.
- Compatibility with lots of other plugins that use Google Analytics library of different versions.
- "client_id is empty" is no more a problem, should be fixed.

### Changed:
- For SendGrid and Mailgun allow using custom defined attachments names if present. Fallback to file name.
- Gmail: switch to a wider scope to prevent possible issues in certain circumstances.
- Remove whitespaces start/end of keys, secrets etc.
- Improved helpful description tests of various options.
- Improved plugin autoloading functionality.

## [1.2.5] - 2018-02-05
### Fixed:
- `Return path` can't be turned off.
- `Authentication` sometimes can't be turned off.
- `Auto TLS` sometimes can't be turned off.
- BCC support for Gmail was broken.
- Debug output improved to handle SELinux and grsecurity.
- Strip slashes from plugin settings (useful for `From Name` option).
- Change the way sanitization is done to prevent accidental removal of useful data.
- Plugin activation will not overwrite settings back to defaults.
- Properly set `Auto TLS` option on plugin activation.
- Providers autoloading improved for certain Windows-based installs.
- Use the proper path to load translations from plugin's `/languages` directory.

### Changed:
- Do not autoload on each page request plugin settings from WordPress options table.
- Do not autoload Pepipost classes unless it's saved as active mailer in settings.

## [1.2.4] - 2018-01-28
### Fixed:
- Improved escaping in debug reporting.

## [1.2.3] - 2018-01-22
### Fixed:
- Gmail tokens were reset after clicking Save Settings.
- Slight typo in Gmail success message.

## [1.2.2] - 2017-12-27
### Fixed:
- Correctly handle Mailgun debug message for an incorrect api key.
- Fatal error for Gmail and SMTP mailers with Nginx web-server (without Apache at all).

### Changed:
- Update X-Mailer emails header to show the real sender with a mailer and plugin version.

## [1.2.1] - 2017-12-21
### Fixed:
- Failed SMTP connections generate fatal errors.

## [1.2.0] - 2017-12-21
### Fixed:
- Decrease the factual minimum WordPress version from 3.9 to 3.6.

### Changed:
- Improve debug output for all mail providers.

## [1.1.0] - 2017-12-18
### Added:
- New option "Auto TLS" for SMTP mailer. Default is enabled. Migration routine for all sites.

### Changed:
- Improve debug output - clear styles and context-aware content.
- Better exceptions handling for Google authentication process.
- Do not sanitize passwords, api keys etc - as they may contain special characters in certain order and sanitization will break those values.
- Improve wording of some helpful texts inside plugin admin area.

### Fixed:
- Do not include certain files in dependency libraries that are not used by Google mailer. This should stop flagging plugin by Wordfence and VaultPress.
- Constants usage is working now, to define the SMTP password, for example.
- Notice for default mailer.

## [1.0.2] - 2017-12-12
### Fixed
- PHPMailer using incorrect SMTPSecure value.

## [1.0.1] - 2017-12-12
### Fixed
- Global POST processing conflict.

## [1.0.0] - 2017-12-12
### Added
- Automatic migration tool to move options from older storage format to a new one.
- Added Gmail & G Suite email provider integration - without your email and password.
- Added SendGrid email provider integration - using the API key only.
- Added Mailgun email provider integration - using the API key and configured domain only.
- New compatibility mode - for PHP 5.2 old plugin will be loaded, for PHP 5.3 and higher - new version of admin area and new functionality.

### Changed
- The new look of the admin area.
- SMTP password field now has "password" type.
- SMTP password field does not display real password at all when using constants in `wp-config.php` to define it.
- Escape properly all translations.
- More helpful test email content (with a mailer name).

## [0.11.2] - 2017-11-28
### Added
- Setting to hide announcement feed.

### Changed
- Announcement feed data.

## [0.11.1] - 2017-10-30
### Changed
- Older PHP compatibility fix.

## [0.11] - 2017-10-30

### Added
- Composer support.
- PHPCS support.
- Build system based on `gulp`.
- Helper description to Return Path option.
- Filter `wp_mail_smtp_admin_test_email_smtp_debug` to increase the debug message verbosity.
- PHP 5.2 notice.
- Announcement feed.

### Changed
- Localization fixes, proper locale name.
- Code style improvements and optimizations for both HTML and PHP.
- Inputs for emails now have a proper type `email`, instead of a generic `text`.
- Turn off `$phpmailer->SMTPAutoTLS` when `No encryption` option is set to prevent error while sending emails.
- Hide Pepipost for those who are not using it.
- WP CLI support improved.
