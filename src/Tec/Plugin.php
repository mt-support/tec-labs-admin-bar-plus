<?php
/**
 * Plugin Class.
 *
 * @since 1.0.0
 *
 * @package Tec\Extensions\Admin_Bar_Plus
 */

namespace Tec\Extensions\Admin_Bar_Plus;

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
	const VERSION = '1.0.0';

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

		add_action( 'admin_bar_menu', [ $this, 'add_toolbar_items' ], 100 );
		add_action( 'admin_menu', [ $this, 'add_submenu_items' ], 11 );

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
	 * @return string
     *
	 * @see \Tec\Extensions\Admin_Bar_Plus\Settings::set_options_prefix()
	 *
	 * TODO: Remove if not using settings
	 */
	private function get_options_prefix() {
		return (string) str_replace( '-', '_', 'tribe-ext-admin-bar-plus' );
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
	 * @param $option
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
				'href'   => 'edit.php?page=tribe-common&tab=general&post_type=tribe_events',
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
				'href'   => 'edit.php?page=tribe-common&tab=display&post_type=tribe_events',
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
				'href'   => 'edit.php?page=tribe-common&tab=licenses&post_type=tribe_events',
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
				'href'   => 'edit.php?page=tribe-common&tab=addons&post_type=tribe_events',
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
				'href'   => 'edit.php?page=tribe-common&tab=imports&post_type=tribe_events',
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
				'href'   => 'edit.php?page=tribe-common&tab=event-tickets&post_type=tribe_events',
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
				'href'   => 'edit.php?page=tribe-common&tab=defaults&post_type=tribe_events',
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
				'href'   => 'edit.php?page=tribe-common&tab=additional-fields&post_type=tribe_events',
				'meta'   => [
					'title' => __( 'Additional Fields', 'tribe-events-calendar-pro' ),
					'class' => 'my_menu_item_class',
				],
			]
		);
	}

	/**
	 * Add submenu items.
	 */
	public function add_submenu_items() {
		add_submenu_page(
			'edit.php?post_type=tribe_events',
			'',
			'-> ' . __( 'General', 'tribe-common' ),
			'manage_options',
			'edit.php?page=tribe-common&tab=general&post_type=tribe_events'
		);

		add_submenu_page(
			'edit.php?post_type=tribe_events',
			'',
			'-> ' . __( 'Display', 'tribe-common' ),
			'manage_options',
			'edit.php?page=tribe-common&tab=display&post_type=tribe_events'
		);

		if ( $this->et_active ) {
			add_submenu_page(
				'edit.php?post_type=tribe_events', '',
				'-> ' . __( 'Tickets', 'event-tickets' ),
				'manage_options',
				'edit.php?page=tribe-common&tab=event-tickets&post_type=tribe_events'
			);
		}

		if ( $this->ecp_active ) {
			add_submenu_page(
				'edit.php?post_type=tribe_events', '',
				'-> ' . __( 'Default Content', 'tribe-events-calendar-pro' ),
				'manage_options',
				'edit.php?page=tribe-common&tab=defaults&post_type=tribe_events'
			);

			add_submenu_page(
				'edit.php?post_type=tribe_events',
				'',
				'-> ' . __( 'Additional Fields', 'tribe-events-calendar-pro' ),
				'manage_options',
				'edit.php?page=tribe-common&tab=additional-fields&post_type=tribe_events'
			);
		}

		if ( $this->ce_active ) {
			add_submenu_page(
				'edit.php?post_type=tribe_events', '',
				'-> ' . __( 'Community', 'tribe-events-community' ),
				'manage_options',
				'edit.php?page=tribe-common&tab=community&post_type=tribe_events'
			);
		}

		if ( $this->fb_active ) {
			add_submenu_page(
				'edit.php?post_type=tribe_events', '',
				'-> ' . __( 'Filters', 'tribe-events-filter-view' ),
				'manage_options',
				'edit.php?page=tribe-common&tab=filter-view&post_type=tribe_events'
			);
		}

		add_submenu_page(
			'edit.php?post_type=tribe_events', '',
			'-> ' . __( 'Licenses', 'tribe-common' ),
			'manage_options',
			'edit.php?page=tribe-common&tab=licenses&post_type=tribe_events'
		);

		add_submenu_page(
			'edit.php?post_type=tribe_events',
			'',
			'-> ' . __( 'APIs', 'tribe-common' ),
			'manage_options',
			'edit.php?page=tribe-common&tab=addons&post_type=tribe_events'
		);

		add_submenu_page(
			'edit.php?post_type=tribe_events', '',
			'-> ' . __( 'Imports', 'the-events-calendar' ),
			'manage_options',
			'edit.php?page=tribe-common&tab=imports&post_type=tribe_events'
		);
	}
}
