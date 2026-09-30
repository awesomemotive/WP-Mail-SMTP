<?php

namespace WPMailSMTP\Providers\Preflight;

/**
 * Codes shared by every provider, so the client writes each message once.
 *
 * A concept two or more providers can observe lives here unprefixed; a concept only one
 * provider has is declared on that provider's own Preflight class with a provider prefix.
 * Severity and field stay per finding, so one code may be an error for one provider and a
 * warning for another.
 *
 * @since 4.10.0
 */
class Code {

	/**
	 * Any credential rejection, including insufficient permissions.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const AUTH_FAILED = 'auth_failed';

	/**
	 * The provider signalled throttling.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const RATE_LIMITED = 'rate_limited';

	/**
	 * The provider is not answering usefully.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const PROVIDER_UNAVAILABLE = 'provider_unavailable';

	/**
	 * Transport failure before any provider response.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const NETWORK_ERROR = 'network_error';

	/**
	 * Unrecognized envelope, unparseable body, or a machine-readable code we do not know.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const UNEXPECTED_RESPONSE = 'unexpected_response';

	/**
	 * A field the check cannot run without arrived empty, so no request was made.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const CONFIG_INCOMPLETE = 'config_incomplete';

	/**
	 * The domain is not on this account.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const DOMAIN_NOT_FOUND = 'domain_not_found';

	/**
	 * The credential authenticates on a provider region other than the configured one.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const REGION_MISMATCH = 'region_mismatch';
}
