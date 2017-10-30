# Changelog

All notable changes to this project will be documented in this file.

## [0.11] - 2017-10-30

### Added
- Composer support.
- PHPCS support.
- Build system based on `gulp`.
- Helper description to Return Path option.
- Filter `wp_mail_smtp_admin_test_email_smtp_debug` to increase the debug message verbosity.
- PHP 5.2 notice.

### Changed
- Localization fixes, proper locale name.
- Code style improvements and optimizations for both HTML and PHP.
- Inputs for emails now have a proper type `email`, instead of a generic `text`.
- Turn off `$phpmailer->SMTPAutoTLS` when `No encryption` option is set to prevent error while sending emails.
- Hide Pepipost for those who are not using it.
- WP CLI support improved.
