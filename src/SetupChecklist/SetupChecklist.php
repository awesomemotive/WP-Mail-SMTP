<?php

namespace WPMailSMTP\SetupChecklist;

use WPMailSMTP\PartnerPlugins\Catalog;
use WPMailSMTP\SettingsImport\SettingsImport;

/**
 * Setup Checklist orchestrator.
 *
 * @since 4.10.0
 */
class SetupChecklist {

	/**
	 * Checklist model: sections, items, completion, and progress.
	 *
	 * @since 4.10.0
	 *
	 * @var Checklist
	 */
	private $checklist;

	/**
	 * Per-site state store.
	 *
	 * @since 4.10.0
	 *
	 * @var State
	 */
	private $state;

	/**
	 * Promo sections model (the Pro upsell and the section chrome).
	 *
	 * @since 4.10.0
	 *
	 * @var Promos
	 */
	private $promos;

	/**
	 * The partner plugins the checklist recommends.
	 *
	 * @since 4.10.0
	 *
	 * @var GrowthTools
	 */
	private $growth_tools;

	/**
	 * Admin menu item (sidebar entry + progress bar).
	 *
	 * @since 4.10.0
	 *
	 * @var Menu
	 */
	private $menu;

	/**
	 * Admin page controller (the checklist page route).
	 *
	 * @since 4.10.0
	 *
	 * @var Page
	 */
	private $page;

	/**
	 * AJAX endpoints (the footer dismiss action).
	 *
	 * @since 4.10.0
	 *
	 * @var Ajax
	 */
	private $ajax;

	/**
	 * Initialize the orchestrator.
	 *
	 * @since 4.10.0
	 */
	public function init(): void {

		$this->state = new State();

		// Registered before the is_admin() bail: the hosted Setup Wizard's REST routes run with is_admin() false.
		$this->state->hooks();
		$this->hooks();

		if ( ! is_admin() ) {
			return;
		}

		$settings_import    = new SettingsImport();
		$catalog            = new Catalog();
		$this->promos       = new Promos();
		$this->growth_tools = new GrowthTools( $catalog );

		$detector = new CompletionDetector( $this->state, $settings_import );

		$this->checklist = new Checklist(
			new Config( $settings_import, $this->promos, $catalog, $detector ),
			$detector
		);
		$this->page      = new Page( $this->checklist, $this->promos, $this->growth_tools );
		$this->menu      = new Menu( $this->checklist, $this->state, $this->page );
		$this->ajax      = new Ajax( $this->checklist, $this->state, $catalog );

		$this->admin_hooks();
	}

	/**
	 * Register hooks that apply on every request.
	 *
	 * @since 4.10.0
	 */
	private function hooks(): void {

		add_filter( 'wp_mail_smtp_upgrade_upgrades', [ $this, 'add_upgrades' ], 10, 2 );
	}

	/**
	 * Queue the routine that reflects an existing install's setup in the checklist.
	 *
	 * @since 4.10.0
	 *
	 * @param array        $upgrades Upgrade callbacks to run.
	 * @param string|false $version  Version the site is upgrading from.
	 *
	 * @return array
	 */
	public function add_upgrades( $upgrades, $version ) {

		// 4.10.0 introduced the checklist, so a site already on it has been recording for itself.
		if ( empty( $version ) || version_compare( $version, '4.10.0', '>=' ) ) {
			return $upgrades;
		}

		$upgrades[] = [ $this, 'seed_test_email_item' ];

		return $upgrades;
	}

	/**
	 * Complete the test-email item on a site that was already sending.
	 *
	 * @since 4.10.0
	 */
	public function seed_test_email_item() {

		if ( wp_mail_smtp()->get_reports()->get_total_emails_sent() > 0 ) {
			$this->state->set_test_email_sent();
		}
	}

	/**
	 * Get the checklist model, or null when the orchestrator has not initialized the
	 * admin-only object graph (a non-admin context, e.g. a REST request).
	 *
	 * @since 4.10.0
	 *
	 * @return Checklist|null
	 */
	public function get_checklist(): ?Checklist {

		return $this->checklist;
	}

	/**
	 * Get the per-site state store.
	 *
	 * @since 4.10.0
	 *
	 * @return State
	 */
	public function get_state(): State {

		return $this->state;
	}

	/**
	 * Register the admin-only menu, page, and AJAX hooks.
	 *
	 * @since 4.10.0
	 */
	private function admin_hooks(): void {

		$this->menu->hooks();
		$this->page->hooks();
		$this->ajax->hooks();
	}
}
