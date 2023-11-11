<?php
/**
 * Plugin Class.
 *
 * @since 1.0.0
 *
 * @package Tec\Extensions\Admin_Bar_Plus
 */

namespace Tec\Extensions\Admin_Bar_Plus;

use TEC\Common\Contracts\Service_Provider;
use Tribe__Dependency;
use WP_Admin_Bar;

/**
 * Class Plugin
 *
 * @since 1.0.0
 *
 * @package Tec\Extensions\Admin_Bar_Plus
 */
class Plugin extends Service_Provider {
	/**
	 * Stores the version for the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const VERSION = '2.1.0';

	/**
	 * Stores the base slug for the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const SLUG = 'admin-bar-plus';

	/**
	 * Stores the base slug for the extension.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const FILE = TEC_EXTENSION_ADMIN_BAR_PLUS_FILE;

	/**
	 * @since 1.0.0
	 *
	 * @var string Plugin Directory.
	 */
	public string $plugin_dir;

	/**
	 * @since 1.0.0
	 *
	 * @var string Plugin path.
	 */
	public string $plugin_path;

	/**
	 * @since 1.0.0
	 *
	 * @var string Plugin URL.
	 */
	public string $plugin_url;

	/**
	 * Is The Events Calendar active. If yes, we will add some extra functionality.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public bool $tec_active = false;

	/**
	 * Is Events Calendar PRO active. If yes, we will add some extra functionality.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public bool $ecp_active = false;

	/**
	 * Is Event Tickets active. If yes, we will add some extra functionality.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public bool $et_active = false;

	/**
	 * Is Event Tickets Plus active. If yes, we will add some extra functionality.
	 *
	 * @since 2.1.0
	 *
	 * @return bool
	 */
	public bool $etp_active = false;

	/**
	 * Is Event Tickets Wallet Plus active. If yes, we will add some extra functionality.
	 *
	 * @since 2.1.0
	 *
	 * @return bool
	 */
	public bool $etwp_active = false;

	/**
	 * Is Filter Bar active. If yes, we will add some extra functionality.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public bool $fb_active = false;

	/**
	 * Is Community Events active. If yes, we will add some extra functionality.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public bool $ce_active = false;

	/**
	 * Set up the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 *
	 * @since 1.0.0
	 */
	public function register() {
		// Set up the plugin provider properties.
		$this->plugin_path = trailingslashit( dirname( static::FILE ) );
		$this->plugin_dir  = trailingslashit( basename( $this->plugin_path ) );
		$this->plugin_url  = plugins_url( $this->plugin_dir, $this->plugin_path );

		// Register this provider as the main one and use a bunch of aliases.
		$this->container->singleton( static::class, $this );
		$this->container->singleton( 'extension.admin_bar_plus', $this );
		$this->container->singleton( 'extension.admin_bar_plus.plugin', $this );
		$this->container->register( PUE::class );

		/*if ( ! $this->check_plugin_dependencies() ) {
			// If the plugin dependency manifest is not met, then bail and stop here.
			return;
		}*/

		// Start binds.
		add_action( 'tribe_plugins_loaded', [ $this, 'detect_tribe_plugins' ], 0 );

		add_action( 'admin_bar_menu', [ $this, 'add_toolbar_items_tec_events' ], 100 );
		add_action( 'admin_bar_menu', [ $this, 'add_toolbar_items_tec_tickets' ], 1999 );
		add_action( 'init', [ $this, 'launch_admin_menu' ] );
		// End binds.

		$this->container->register( Hooks::class );
		$this->container->register( Assets::class );
	}

	/**
	 * Checks whether the plugin dependency manifest is satisfied or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the plugin dependency manifest is satisfied or not.
	 */
	protected function check_plugin_dependencies(): bool {
		$this->register_plugin_dependencies();

		return tribe_check_plugin( static::class );
	}

	/**
	 * Registers the plugin and dependency manifest among those managed by Tribe Common.
	 *
	 * @since 1.0.0
	 */
	protected function register_plugin_dependencies() {
		$plugin_register = new Plugin_Register();
		$plugin_register->register_plugin();

		$this->container->singleton( Plugin_Register::class, $plugin_register );
		$this->container->singleton( 'extension.admin_bar_plus', $plugin_register );
	}

	/**
	 * Check required plugins after all Tribe plugins have loaded.
	 *
	 * Useful for conditionally-requiring a Tribe plugin, whether to add extra functionality
	 * or require a certain version but only if it is active.
	 */
	public function detect_tribe_plugins() {
		/** @var Tribe__Dependency $dep */
		$dep = tribe( Tribe__Dependency::class );

		$this->tec_active = $dep->is_plugin_active( 'Tribe__Events__Main' );

		$this->ecp_active = $dep->is_plugin_active( 'Tribe__Events__Pro__Main' );

		$this->et_active = $dep->is_plugin_active( 'Tribe__Tickets__Main' );

		$this->etp_active = $dep->is_plugin_active( 'Tribe__Tickets_Plus__Main' );

		$this->etwp_active = $dep->is_plugin_active( '\TEC\Tickets_Wallet_Plus\Plugin' );

		$this->fb_active = $dep->is_plugin_active( 'Tribe__Events__Filterbar__View' );

		$this->ce_active = $dep->is_plugin_active( 'Tribe__Events__Community__Main' );
	}

	/**
	 * Add our custom menu items, as applicable.
	 *
	 * @param WP_Admin_Bar $admin_bar
	 */
	public function add_toolbar_items_tec_events( WP_Admin_Bar $admin_bar ) {

		$admin_bar->add_menu(
			[
				'id'     => 'tribe-events-settings-general',
				'parent' => 'tribe-events-settings',
				'title'  => __( 'General', 'the-events-calendar' ),
				'href'   => 'edit.php?post_type=tribe_events&page=tec-events-settings&tab=general',
				'meta'   => [
					'title' => __( 'General', 'the-events-calendar' ),
					'class' => 'my_menu_item_class',
				],
			]
		);

		$admin_bar->add_menu(
			[
				'id'     => 'tribe-events-settings-display',
				'parent' => 'tribe-events-settings',
				'title'  => __( 'Display', 'the-events-calendar' ),
				'href'   => 'edit.php?post_type=tribe_events&page=tec-events-settings&tab=display',
				'meta'   => [
					'title' => __( 'Display', 'the-events-calendar' ),
					'class' => 'my_menu_item_class',
				],
			]
		);

		$this->maybe_add_toolbar_items_ecp( $admin_bar );

		$this->maybe_add_toolbar_items_ce( $admin_bar );

		$this->maybe_add_toolbar_items_filterbar( $admin_bar );

		$admin_bar->add_menu(
			[
				'id'     => 'tribe-events-settings-licenses',
				'parent' => 'tribe-events-settings',
				'title'  => __( 'Licenses', 'tribe-common' ),
				'href'   => 'edit.php?post_type=tribe_events&page=tec-events-settings&tab=licenses',
				'meta'   => [
					'title' => __( 'Licenses', 'tribe-common' ),
					'class' => 'my_menu_item_class',
				],
			]
		);

		$admin_bar->add_menu(
			[
				'id'     => 'tribe-events-settings-apis',
				'parent' => 'tribe-events-settings',
				'title'  => __( 'Integrations', 'the-events-calendar' ),
				'href'   => 'edit.php?post_type=tribe_events&page=tec-events-settings&tab=addons',
				'meta'   => [
					'title' => __( 'Integrations', 'the-events-calendar' ),
					'class' => 'my_menu_item_class',
				],
			]
		);

		$admin_bar->add_menu(
			[
				'id'     => 'tribe-events-settings-imports',
				'parent' => 'tribe-events-settings',
				'title'  => __( 'Imports', 'the-events-calendar' ),
				'href'   => 'edit.php?post_type=tribe_events&page=tec-events-settings&tab=imports',
				'meta'   => [
					'title' => __( 'Imports', 'the-events-calendar' ),
					'class' => 'my_menu_item_class',
				],
			]
		);
	}

	/**
	 * Add Events Calendar Pro's custom menu items.
	 *
	 * @param WP_Admin_Bar $admin_bar
	 */
	public function maybe_add_toolbar_items_ecp( WP_Admin_Bar $admin_bar ) {
		if ( ! $this->ecp_active ) {
			return;
		}

		$admin_bar->add_menu(
			[
				'id'     => 'tribe-events-settings-default-content',
				'parent' => 'tribe-events-settings',
				'title'  => __( 'Default Content', 'tribe-events-calendar-pro' ),
				'href'   => 'edit.php?post_type=tribe_events&page=tec-events-settings&tab=defaults',
				'meta'   => [
					'title' => __( 'Default Content', 'tribe-events-calendar-pro' ),
					'class' => 'my_menu_item_class',
				],
			]
		);

		$admin_bar->add_menu(
			[
				'id'     => 'tribe-events-settings-additional-fields',
				'parent' => 'tribe-events-settings',
				'title'  => __( 'Additional Fields', 'tribe-events-calendar-pro' ),
				'href'   => 'edit.php?post_type=tribe_events&page=tec-events-settings&tab=additional-fields',
				'meta'   => [
					'title' => __( 'Additional Fields', 'tribe-events-calendar-pro' ),
					'class' => 'my_menu_item_class',
				],
			]
		);
	}

	/**
	 * Add Community Events' custom menu items.
	 *
	 * @param WP_Admin_Bar $admin_bar
	 */
	public function maybe_add_toolbar_items_ce( WP_Admin_Bar $admin_bar ) {
		if ( ! $this->ce_active ) {
			return;
		}

		$admin_bar->add_menu(
			[
				'id'     => 'tribe-events-settings-community',
				'parent' => 'tribe-events-settings',
				'title'  => __( 'Community', 'tribe-events-community' ),
				'href'   => 'edit.php?post_type=tribe_events&page=tec-events-settings&tab=community',
				'meta'   => [
					'title' => __( 'Community', 'tribe-events-community' ),
					'class' => 'my_menu_item_class',
				],
			]
		);
	}

	/**
	 * Add Filter Bar's custom menu items.
	 *
	 * @param WP_Admin_Bar $admin_bar
	 */
	public function maybe_add_toolbar_items_filterbar( WP_Admin_Bar $admin_bar ) {
		if ( ! $this->fb_active ) {
			return;
		}

		$admin_bar->add_menu(
			[
				'id'     => 'tribe-events-settings-filters',
				'parent' => 'tribe-events-settings',
				'title'  => __( 'Filters', 'tribe-common' ),
				'href'   => 'edit.php?post_type=tribe_events&page=tec-events-settings&tab=filter-view',
				'meta'   => [
					'title' => __( 'Filters', 'tribe-common' ),
					'class' => 'my_menu_item_class',
				],
			]
		);
	}

	/**
	 * Add our custom menu items, as applicable.
	 *
	 * @param WP_Admin_Bar $admin_bar
	 */
	public function add_toolbar_items_tec_tickets( WP_Admin_Bar $admin_bar ) {

		if ( ! $this->et_active ) {
			return;
		}

		$admin_bar->add_menu(
			[
				'id'     => 'tribe-tickets',
				'parent' => false,
				'title'  => __( 'Tickets', 'event-tickets' ),
				'href'   => 'admin.php?page=tec-tickets',
				'meta'   => [
					'title' => __( 'Tickets', 'event-tickets' ),
					'class' => 'my_menu_item_class',
				],
			]
		);

		$admin_bar->add_menu(
			[
				'id'     => 'tribe-tickets-settings',
				'parent' => 'tribe-tickets',
				'title'  => __( 'Settings', 'event-tickets' ),
				'href'   => 'admin.php?page=tec-tickets-settings&tab=general',
				'meta'   => [
					'title' => __( 'Settings', 'event-tickets' ),
					'class' => 'my_menu_item_class',
				],
			]
		);

		$admin_bar->add_menu(
			[
				'id'     => 'tribe-tickets-div',
				'parent' => 'tribe-tickets',
				'title'  => '&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;',
			]
		);

		$admin_bar->add_menu(
			[
				'id'     => 'tribe-tickets-settings-general',
				'parent' => 'tribe-tickets',
				'title'  => __( 'General', 'event-tickets' ),
				'href'   => 'admin.php?page=tec-tickets-settings&tab=event-tickets',
				'meta'   => [
					'title' => __( 'General', 'event-tickets' ),
					'class' => 'my_menu_item_class',
				],
			]
		);

		$admin_bar->add_menu(
			[
				'id'     => 'tribe-tickets-settings-payments',
				'parent' => 'tribe-tickets',
				'title'  => __( 'Payments', 'event-tickets' ),
				'href'   => 'admin.php?page=tec-tickets-settings&tab=payments',
				'meta'   => [
					'title' => __( 'Payments', 'event-tickets' ),
					'class' => 'my_menu_item_class',
				],
			]
		);

		if ( tribe_get_option( 'tickets_commerce_enabled' ) ) {
			$admin_bar->add_menu(
				[
					'id'     => 'tribe-tickets-settings-payments-stripe',
					'parent' => 'tribe-tickets',
					'title'  => '&#8594; ' . __( 'Stripe', 'event-tickets' ),
					'href'   => 'admin.php?page=tec-tickets-settings&tab=payments&tc-section=stripe',
					'meta'   => [
						'title' => __( 'Stripe', 'event-tickets' ),
						'class' => 'my_menu_item_class',
					],
				]
			);

			$admin_bar->add_menu(
				[
					'id'     => 'tribe-tickets-settings-payments-paypal',
					'parent' => 'tribe-tickets',
					'title'  => '&#8594; ' . __( 'PayPal', 'event-tickets' ),
					'href'   => 'admin.php?page=tec-tickets-settings&tab=payments&tc-section=paypal',
					'meta'   => [
						'title' => __( 'PayPal', 'event-tickets' ),
						'class' => 'my_menu_item_class',
					],
				]
			);
		}

		$admin_bar->add_menu(
			[
				'id'     => 'tribe-tickets-settings-emails',
				'parent' => 'tribe-tickets',
				'title'  => __( 'Emails', 'event-tickets' ),
				'href'   => 'admin.php?page=tec-tickets-settings&tab=emails',
				'meta'   => [
					'title' => __( 'Emails', 'event-tickets' ),
					'class' => 'my_menu_item_class',
				],
			]
		);

		$this->maybe_add_toolbar_item_attendee_registration( $admin_bar );

		$this->maybe_add_toolbar_items_etwp( $admin_bar );

		$this->maybe_add_toolbar_item_integrations( $admin_bar );

		$admin_bar->add_menu(
			[
				'id'     => 'tribe-tickets-settings-licenses',
				'parent' => 'tribe-tickets',
				'title'  => __( 'Licenses', 'tribe-common' ),
				'href'   => 'admin.php?page=tec-tickets-settings&tab=licenses',
				'meta'   => [
					'title' => __( 'Licenses', 'tribe-common' ),
					'class' => 'my_menu_item_class',
				],
			]
		);
	}

	/**
	 * Add links to the Attendee Registration page to the admin bar menu, when ETP is active.
	 *
	 * @param WP_Admin_Bar $admin_bar
	 *
	 * @return void
	 *
	 * @since 2.1.0
	 */
	public function maybe_add_toolbar_item_attendee_registration( WP_Admin_Bar $admin_bar ) {
		if ( ! $this->etp_active ) {
			return;
		}

		$admin_bar->add_menu(
			[
				'id'     => 'tribe-tickets-settings-attendee-registration',
				'parent' => 'tribe-tickets',
				'title'  => __( 'Attendee Registration', 'event-tickets-plus' ),
				'href'   => 'admin.php?page=tec-tickets-settings&tab=attendee-registration',
				'meta'   => [
					'title' => __( 'Attendee Registration', 'event-tickets-plus' ),
					'class' => 'my_menu_item_class',
				],
			]
		);
	}

	/**
	 * Add links to the Integrations page to the admin bar menu, when ETP is active.
	 *
	 * @param WP_Admin_Bar $admin_bar
	 *
	 * @return void
	 *
	 * @since 2.1.0
	 */
	public function maybe_add_toolbar_item_integrations( WP_Admin_Bar $admin_bar ) {
		if ( ! $this->etp_active ) {
			return;
		}

		$admin_bar->add_menu(
			[
				'id'     => 'tribe-tickets-settings-integrations',
				'parent' => 'tribe-tickets',
				'title'  => __( 'Integrations', 'event-tickets-plus' ),
				'href'   => 'admin.php?page=tec-tickets-settings&tab=integrations',
				'meta'   => [
					'title' => __( 'Integrations', 'event-tickets-plus' ),
					'class' => 'my_menu_item_class',
				],
			]
		);
	}

	/**
	 * Add links to the Event Tickets Wallet Plus pages to the admin bar menu, when ETWP is active.
	 *
	 * @param WP_Admin_Bar $admin_bar
	 *
	 * @return void
	 *
	 * @since 2.1.0
	 */
	public function maybe_add_toolbar_items_etwp( WP_Admin_Bar $admin_bar ) {
		if ( ! $this->etwp_active ) {
			return;
		}

		$admin_bar->add_menu(
			[
				'id'     => 'tribe-tickets-settings-wallet-plus',
				'parent' => 'tribe-tickets',
				'title'  => __( 'Wallet Plus', 'event-tickets-wallet-plus' ),
				'href'   => 'admin.php?page=tec-tickets-settings&tab=wallet',
				'meta'   => [
					'title' => __( 'Wallet Plus', 'event-tickets-wallet-plus' ),
					'class' => 'my_menu_item_class',
				],
			]
		);

		$admin_bar->add_menu(
			[
				'id'     => 'tribe-tickets-settings-wallet-plus-apple-wallet',
				'parent' => 'tribe-tickets-settings',
				'title'  => '&#8594; ' . __( 'Apple Wallet', 'event-tickets-wallet-plus' ),
				'href'   => 'admin.php?page=tec-tickets-settings&tab=wallet',
				'meta'   => [
					'title' => __( 'Apple Wallet', 'event-tickets-wallet-plus' ),
					'class' => 'my_menu_item_class',
				],
			]
		);

		$admin_bar->add_menu(
			[
				'id'     => 'tribe-tickets-settings-wallet-plus-pdf-tickets',
				'parent' => 'tribe-tickets-settings',
				'title'  => '&#8594; ' . __( 'PDF Tickets', 'event-tickets-wallet-plus' ),
				'href'   => 'admin.php?page=tec-tickets-settings&tab=wallet&section=pdf-tickets',
				'meta'   => [
					'title' => __( 'PDF Tickets', 'event-tickets-wallet-plus' ),
					'class' => 'my_menu_item_class',
				],
			]
		);
	}

	/**
	 * Add menu items based on active plugin.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function launch_admin_menu() {

		if ( $this->tec_active ) {
			add_action( 'admin_menu', [ $this, 'add_tec_submenu_items' ] );
		}

		if ( $this->et_active ) {
			add_action( 'admin_menu', [ $this, 'add_tickets_submenu_items' ], 9 );
		}
	}

	/**
	 * Add TEC submenu items.
	 */
	public function add_tec_submenu_items() {
		$admin_pages = tribe( 'admin.pages' );

		$admin_pages->register_page(
			[
				'id'       => 'tec-events-settings-2',
				'parent'   => 'edit.php?post_type=tribe_events',
				'title'    => esc_html__( 'Settings', 'tribe-common' ),
				'path'     => 'tec-events-settings',
			]
		);

		$admin_pages->register_page(
			[
				'id'       => 'tec-events-settings-general',
				'parent'   => 'edit.php?post_type=tribe_events',
				'title'    => '&#8594; ' . esc_html__( 'General', 'the-events-calendar' ),
				'path'     => 'tec-events-settings&tab=general',
			]
		);

		$admin_pages->register_page(
			[
				'id'       => 'tec-events-settings-display',
				'parent'   => 'edit.php?post_type=tribe_events',
				'title'    => '&#8594; ' . esc_html__( 'Display', 'the-events-calendar' ),
				'path'     => 'tec-events-settings&tab=display',
			]
		);

		if ( $this->ecp_active ) {
			$admin_pages->register_page(
				[
					'id'       => 'tec-events-pro-settings-defaults',
					'parent'   => 'edit.php?post_type=tribe_events',
					'title'    => '&#8594; ' . esc_html__( 'Default Content', 'tribe-events-calendar-pro' ),
					'path'     => 'tec-events-settings&tab=defaults',
				]
			);

			$admin_pages->register_page(
				[
					'id'       => 'tec-events-pro-settings-additional-fields',
					'parent'   => 'edit.php?post_type=tribe_events',
					'title'    => '&#8594; ' . esc_html__( 'Additional Fields', 'tribe-events-calendar-pro' ),
					'path'     => 'tec-events-settings&tab=additional-fields',
				]
			);
		}

		if ( $this->ce_active ) {
			$admin_pages->register_page(
				[
					'id'       => 'tec-events-community-settings-community',
					'parent'   => 'edit.php?post_type=tribe_events',
					'title'    => '&#8594; ' . esc_html__( 'Community', 'tribe-events-community' ),
					'path'     => 'tec-events-settings&tab=community',
				]
			);
		}

		if ( $this->fb_active ) {
			$admin_pages->register_page(
				[
					'id'       => 'tec-events-filterbar-settings-filterbar',
					'parent'   => 'edit.php?post_type=tribe_events',
					'title'    => '&#8594; ' . esc_html__( 'Filters', 'tribe-common' ),
					'path'     => 'tec-events-settings&tab=filter-view',
				]
			);
		}

		$admin_pages->register_page(
			[
				'id'       => 'tec-events-settings-licenses',
				'parent'   => 'edit.php?post_type=tribe_events',
				'title'    => '&#8594; ' . esc_html__( 'Licenses', 'tribe-common' ),
				'path'     => 'tec-events-settings&tab=licenses',
			]
		);

		$admin_pages->register_page(
			[
				'id'       => 'tec-events-settings-integrations',
				'parent'   => 'edit.php?post_type=tribe_events',
				'title'    => '&#8594; ' . esc_html__( 'Integrations', 'tribe-common' ),
				'path'     => 'tec-events-settings&tab=addons',
			]
		);

		$admin_pages->register_page(
			[
				'id'       => 'tec-events-settings-imports',
				'parent'   => 'edit.php?post_type=tribe_events',
				'title'    => '&#8594; ' . esc_html__( 'Imports', 'the-events-calendar' ),
				'path'     => 'tec-events-settings&tab=imports',
			]
		);
	}

	/**
	 * Add Tickets submenu items.
	 */
	public function add_tickets_submenu_items() {
		$admin_pages = tribe( 'admin.pages' );

		$admin_pages->register_page(
			[
				'id'       => 'tec-tickets-settings-2',
				'parent'   => 'tec-tickets',
				'title'    => esc_html__( 'Settings', 'event-tickets' ),
				'path'     => 'tec-tickets-settings',
			]
		);

		$admin_pages->register_page(
			[
				'id'       => 'tec-tickets-general',
				'parent'   => 'tec-tickets',
				'title'    => '&#8594; ' . esc_html__( 'General', 'event-tickets' ),
				'path'     => 'admin.php?page=tec-tickets-settings&tab=event-tickets',
			]
		);

		$admin_pages->register_page(
			[
				'id'       => 'tec-tickets-payments',
				'parent'   => 'tec-tickets',
				'title'    => '&#8594; ' . esc_html__( 'Payments', 'event-tickets' ),
				'path'     => 'admin.php?page=tec-tickets-settings&tab=payments',
			]
		);

		if ( tribe_get_option( 'tickets_commerce_enabled' ) ) {
			$admin_pages->register_page(
				[
					'id'       => 'tec-tickets-payments-stripe',
					'parent'   => 'tec-tickets',
					'title'    => '&ndash;&#8594; ' . esc_html__( 'Stripe', 'event-tickets' ),
					'path'     => 'admin.php?page=tec-tickets-settings&tab=payments&tc-section=stripe',
				]
			);

			$admin_pages->register_page(
				[
					'id'       => 'tec-tickets-payments-paypal',
					'parent'   => 'tec-tickets',
					'title'    => '&ndash;&#8594; ' . esc_html__( 'PayPal', 'event-tickets' ),
					'path'     => 'admin.php?page=tec-tickets-settings&tab=payments&tc-section=paypal',
				]
			);
		}

		$admin_pages->register_page(
			[
				'id'       => 'tec-tickets-emails',
				'parent'   => 'tec-tickets',
				'title'    => '&#8594; ' . esc_html__( 'Emails', 'event-tickets' ),
				'path'     => 'admin.php?page=tec-tickets-settings&tab=emails',
			]
		);

		if ( $this->etp_active ) {
			$admin_pages->register_page(
				[
					'id'     => 'tec-tickets-attendee-registration',
					'parent' => 'tec-tickets',
					'title'  => '&#8594; ' . esc_html__( 'Attendee Registration', 'event-tickets-plus' ),
					'path'   => 'admin.php?page=tec-tickets-settings&tab=attendee-registration',
				]
			);
		}

		if ( $this->etwp_active ) {
			$admin_pages->register_page(
				[
					'id'     => 'tec-tickets-wallet-plus',
					'parent' => 'tec-tickets',
					'title'  => '&#8594; ' . esc_html__( 'Wallet Plus', 'event-tickets-wallet-plus' ),
					'path'   => 'admin.php?page=tec-tickets-settings&tab=wallet',
				]
			);

			$admin_pages->register_page(
				[
					'id'     => 'tec-tickets-wallet-plus-apple-wallet-passes',
					'parent' => 'tec-tickets',
					'title'  => '&ndash;&#8594; ' . esc_html__( 'Apple Wallet', 'event-tickets-wallet-plus' ),
					'path'   => 'admin.php?page=tec-tickets-settings&tab=wallet',
				]
			);

			$admin_pages->register_page(
				[
					'id'     => 'tec-tickets-wallet-plus-pdf-tickets',
					'parent' => 'tec-tickets',
					'title'  => '&ndash;&#8594; ' . esc_html__( 'PDF Tickets', 'event-tickets-wallet-plus' ),
					'path'   => 'admin.php?page=tec-tickets-settings&tab=wallet&section=pdf-tickets',
				]
			);
		}

		if ( $this->etp_active ) {
			$admin_pages->register_page(
				[
					'id'     => 'tec-tickets-integrations',
					'parent' => 'tec-tickets',
					'title'  => '&#8594; ' . esc_html__( 'Integrations', 'event-tickets-plus' ),
					'path'   => 'admin.php?page=tec-tickets-settings&tab=integrations',
				]
			);
		}

		$admin_pages->register_page(
			[
				'id'       => 'tec-tickets-licenses',
				'parent'   => 'tec-tickets',
				'title'    => '&#8594; ' . esc_html__( 'Licenses', 'tribe-common' ),
				'path'     => 'admin.php?page=tec-tickets-settings&tab=licenses',
			]
		);
	}
}
