<?php
/**
 * Plugin Class.
 *
 * @since 1.0.0
 *
 * @package Tec\Extensions\Admin_Bar_Plus
 */

namespace Tec\Extensions\Admin_Bar_Plus;

use Tribe__Dependency;

/**
 * Class Plugin
 *
 * @since 1.0.0
 *
 * @package Tec\Extensions\Admin_Bar_Plus
 */
class Plugin extends \tad_DI52_ServiceProvider {
	/**
	 * Stores the version for the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const VERSION = '2.0.0';

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
	public $plugin_dir;

	/**
	 * @since 1.0.0
	 *
	 * @var string Plugin path.
	 */
	public $plugin_path;

	/**
	 * @since 1.0.0
	 *
	 * @var string Plugin URL.
	 */
	public $plugin_url;

	/**
	 * @since 1.0.0
	 *
	 * @var Settings
	 *
	 * TODO: Remove if not using settings
	 */
	private $settings;

	/**
	 * Is The Events Calendar active. If yes, we will add some extra functionality.
	 *
	 * @return bool
	 */
	public $tec_active = false;

	/**
	 * Is Events Calendar PRO active. If yes, we will add some extra functionality.
	 *
	 * @return bool
	 */
	public $ecp_active = false;

	/**
	 * Is Event Tickets active. If yes, we will add some extra functionality.
	 *
	 * @return bool
	 */
	public $et_active = false;

	/**
	 * Is Filter Bar active. If yes, we will add some extra functionality.
	 *
	 * @return bool
	 */
	public $fb_active = false;

	/**
	 * Is Community Events active. If yes, we will add some extra functionality.
	 *
	 * @return bool
	 */
	public $ce_active = false;

	/**
	 * Setup the Extension's properties.
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

		if ( ! $this->check_plugin_dependencies() ) {
			// If the plugin dependency manifest is not met, then bail and stop here.
			return;
		}

		// Do the settings.
		// TODO: Remove if not using settings
		$this->get_settings();

		// Start binds.
		add_action( 'tribe_plugins_loaded', [ $this, 'detect_tribe_plugins' ], 0 );

		//add_action( 'admin_bar_menu', [ $this, 'add_toolbar_items' ], 100 );
		add_action( 'init', [ $this, 'launch' ] );

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
	protected function check_plugin_dependencies() {
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
	 * Get this plugin's options prefix.
	 *
	 * Settings_Helper will append a trailing underscore before each option.
	 *
	 * @see \Tec\Extensions\Admin_Bar_Plus\Settings::set_options_prefix()
	 *
	 * TODO: Remove if not using settings
	 * @return string
	 *
	 */
	private function get_options_prefix() {
		return (string) str_replace( '-', '_', 'tec-labs-admin-bar-plus' );
	}

	/**
	 * Get Settings instance.
	 *
	 * @return Settings
	 *
	 * TODO: Remove if not using settings
	 */
	private function get_settings() {
		if ( empty( $this->settings ) ) {
			$this->settings = new Settings( $this->get_options_prefix() );
		}

		return $this->settings;
	}

	/**
	 * Get all of this extension's options.
	 *
	 * @return array
	 *
	 * TODO: Remove if not using settings
	 */
	public function get_all_options() {
		$settings = $this->get_settings();

		return $settings->get_all_options();
	}

	/**
	 * Get a specific extension option.
	 *
	 * @param        $option
	 * @param string $default
	 *
	 * @return array
	 *
	 * TODO: Remove if not using settings
	 */
	public function get_option( $option, $default = '' ) {
		$settings = $this->get_settings();

		return $settings->get_option( $option, $default );
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

		if ( $dep->is_plugin_active( 'Tribe__Events__Main' ) ) {
			//$this->add_required_plugin( 'Tribe__Events__Pro__Main' );
			$this->tec_active = true;
		}
		if ( $dep->is_plugin_active( 'Tribe__Events__Pro__Main' ) ) {
			//$this->add_required_plugin( 'Tribe__Events__Pro__Main' );
			$this->ecp_active = true;
		}
		if ( $dep->is_plugin_active( 'Tribe__Tickets__Main' ) ) {
			//$this->add_required_plugin( 'Tribe__Tickets__Main' );
			$this->et_active = true;
		}
		if ( $dep->is_plugin_active( 'Tribe__Events__Filterbar__View' ) ) {
			//$this->add_required_plugin( 'Tribe__Events__Filterbar__View' );
			$this->fb_active = true;
		}
		if ( $dep->is_plugin_active( 'Tribe__Events__Community__Main' ) ) {
			//$this->add_required_plugin( 'Tribe__Events__Community__Main' );
			$this->ce_active = true;
		}
	}

	/**
	 * Add our custom menu items, as applicable.
	 *
	 * @param \WP_Admin_Bar $admin_bar
	 */
	public function add_toolbar_items( $admin_bar ) {
		$admin_bar->add_menu(
			[
				'id'     => 'tribe-events-settings-general',
				'parent' => 'tribe-events-settings',
				'title'  => __( 'General', 'tribe-common' ),
				'href'   => 'edit.php?post_type=tribe_events&page=tec-events-settings&tab=general',
				'meta'   => [
					'title' => __( 'General', 'tribe-common' ),
					'class' => 'my_menu_item_class',
				],
			]
		);

		$admin_bar->add_menu(
			[
				'id'     => 'tribe-events-settings-display',
				'parent' => 'tribe-events-settings',
				'title'  => __( 'Display', 'tribe-common' ),
				'href'   => 'edit.php?post_type=tribe_events&page=tec-events-settings&tab=display',
				'meta'   => [
					'title' => __( 'Display', 'tribe-common' ),
					'class' => 'my_menu_item_class',
				],
			]
		);

		$this->add_toolbar_items_et( $admin_bar );

		$this->add_toolbar_items_ecp( $admin_bar );

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
				'title'  => __( 'APIs', 'tribe-common' ),
				'href'   => 'edit.php?post_type=tribe_events&page=tec-events-settings&tab=addons',
				'meta'   => [
					'title' => __( 'APIs', 'tribe-common' ),
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
	 * Add Event Tickets' custom menu items.
	 *
	 * @param \WP_Admin_Bar $admin_bar
	 */
	public function add_toolbar_items_et( $admin_bar ) {
		if ( ! $this->et_active ) {
			return;
		}

		$admin_bar->add_menu(
			[
				'id'     => 'tribe-events-settings-tickets',
				'parent' => 'tribe-events-settings',
				'title'  => __( 'Tickets', 'event-tickets' ),
				'href'   => 'edit.php?post_type=tribe_events&page=tec-events-settings&tab=event-tickets',
				'meta'   => [
					'title' => __( 'Tickets', 'event-tickets' ),
					'class' => 'my_menu_item_class',
				],
			]
		);
	}

	/**
	 * Add Events Calendar Pro's custom menu items.
	 *
	 * @param \WP_Admin_Bar $admin_bar
	 */
	public function add_toolbar_items_ecp( $admin_bar ) {
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

	public function launch() {

		if ( $this->tec_active ) {
			add_action( 'admin_menu', [ $this, 'add_tec_submenu_items' ], 10 );
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
				'title'    => '&#8594; ' . esc_html__( 'General', 'tribe-common' ),
				'path'     => 'tec-events-settings&tab=general',
			]
		);

		$admin_pages->register_page(
			[
				'id'       => 'tec-events-settings-display',
				'parent'   => 'edit.php?post_type=tribe_events',
				'title'    => '&#8594; ' . esc_html__( 'Display', 'tribe-common' ),
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
				'title'    => esc_html__( 'Settings', 'the-events-calendar' ),
				'path'     => 'tec-tickets-settings',
			]
		);

		$admin_pages->register_page(
			[
				'id'       => 'tec-tickets-general',
				'parent'   => 'tec-tickets',
				'title'    => '&#8594; ' . esc_html__( 'General', 'the-events-calendar' ),
				'path'     => 'tec-tickets-settings&tab=event-tickets',
			]
		);

		$admin_pages->register_page(
			[
				'id'       => 'tec-tickets-payments',
				'parent'   => 'tec-tickets',
				'title'    => '&#8594; ' . esc_html__( 'Payments', 'the-events-calendar' ),
				'path'     => 'tec-tickets-settings&tab=payments',
			]
		);

		$admin_pages->register_page(
			[
				'id'       => 'tec-tickets-licenses',
				'parent'   => 'tec-tickets',
				'title'    => '&#8594; ' . esc_html__( 'Licenses', 'the-events-calendar' ),
				'path'     => 'tec-tickets-settings&tab=licenses',
			]
		);
	}
}