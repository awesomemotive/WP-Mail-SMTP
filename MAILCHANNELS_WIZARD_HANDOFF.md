# MailChannels setup-wizard handoff

The public repository intentionally excludes the `vue-app` source used to build
`assets/vue/js/wizard.min.js`. This branch registers the complete PHP backend,
settings screen, option/constants plumbing, public provider logo, and wizard
mailer metadata. The following private-source change is required before this PR
can leave draft status.

## Component contract

Add `src/components/steps/ConfigureMailer/MailChannels.vue` (or the equivalent
provider-component directory in the current private source) and register it in
the same map that currently resolves `resend` to its configuration component.
The provider slug is exactly `mailchannels`.

The component uses the existing settings-store contract:

- API key model: `settings.mailchannels.api_key` (string, required, sensitive).
- submission-mode model: `settings.mailchannels.send_mode` (enum, `direct` or
  `queued`; initialize missing values to `direct`).
- From Email, From Name, and force toggles remain the shared mailer fields.
- constants arrive from PHP as `WPMS_MAILCHANNELS_API_KEY` and
  `WPMS_MAILCHANNELS_SEND_MODE`; when listed in `defined_constants`, disable the
  matching input and render the standard constant-defined notice.
- the wizard save request must submit the exact backend option shape:

```json
{
  "mail": { "mailer": "mailchannels" },
  "mailchannels": {
    "api_key": "<secret>",
    "send_mode": "direct"
  }
}
```

Use `assets/vue/img/mailchannels.svg` in the private source's image import map.
The public `prepare_mailer_options()` response already supplies:

```json
{
  "slug": "mailchannels",
  "title": "MailChannels",
  "description": "<provider description>",
  "edu_notice": "",
  "min_php": "7.4",
  "disabled": false
}
```

## Exact UI strings

- `API Key`
- `Create or copy an Email API key in the MailChannels Console.`
- `Submission Mode`
- `Direct`
- `Wait for per-message acceptance results.`
- `Queued`
- `Return after MailChannels accepts the request.`
- `Both modes use HTTPS only. WP Mail SMTP does not automatically retry a failed submission.`
- `Accepted by MailChannels does not mean finally delivered.`

Link the API-key help text to `https://console.mailchannels.net/` with
`target="_blank" rel="noopener noreferrer"`.

## Private-source acceptance checks

1. MailChannels appears in the Choose a Mailer step with the supplied logo.
2. Selecting it renders the API-key and direct/queued fields; direct is the
   default.
3. Required-field validation blocks an empty API key and does not echo secrets.
4. Back/forward navigation preserves both fields in the settings store.
5. Constant-defined fields are disabled and masked.
6. Saving produces only the option shape above; the API key never appears in
   browser logs or error telemetry.
7. The configuration-check step performs exactly one Email API submission and
   reports HTTP/API errors through the existing wizard error surface.
8. Rebuild `wizard.min.js`, `wizard.min.css`, RTL CSS, and the Vue translation
   catalog with the maintainer's normal private build.

Do not hand-edit the compiled wizard bundle in this public branch. Maintainers
should attach before/after screenshots of the mailer picker, empty-key error,
direct selection, queued selection, and masked constant state to the PR before
merging.
