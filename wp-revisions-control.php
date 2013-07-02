<?php
/*
Plugin Name: WP Revisions Control
Plugin URI: http://www.ethitter.com/plugins/wp-revisions-control/
Description: Control how many revisions are stored for each post type
Author: Erick Hitter
Version: 1.0
Author URI: http://www.ethitter.com/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class WP_Revisions_Control {
	/**
	 * Singleton
	 */
	private static $__instance = null;

	/**
	 * Class variables
	 */
	private static $priority = null; // use $this->plugin_priority()
	private $priority_default = 50;

	private static $post_types = array(); // use $this->get_post_types()
	private static $settings = array(); // use $this->get_settings()

	private $settings_page = 'writing';
	private $settings_section = 'wp_revisions_control';

	/**
	 * Silence is golden!
	 */
	private function __construct() {}

	/**
	 * Singleton implementation
	 *
	 * @uses self::setup
	 * @return object
	 */
	public static function get_instance() {
		if ( ! is_a( self::$__instance, __CLASS__ ) ) {
			self::$__instance = new self;

			self::$__instance->setup();
		}

		return self::$__instance;
	}

	/**
	 * Register actions and filters at `init` so others can interact, if desired.
	 *
	 * @uses add_action
	 * @return null
	 */
	private function setup() {
		add_action( 'init', array( $this, 'action_init' ) );
	}

	/**
	 * Register actions and filters
	 *
	 * @uses add_action
	 * @uses add_filter
	 * @uses this::plugin_priority
	 * @return null
	 */
	public function action_init() {
		add_action( 'admin_init', array( $this, 'action_admin_init' ) );

		add_filter( 'wp_revisions_to_keep', array( $this, 'filter_wp_revisions_to_keep' ), $this->plugin_priority(), 2 );
	}

	/**
	 * Register plugin's settings fields
	 *
	 * Plugin title is intentionally not translatable.
	 *
	 * @uses register_setting
	 * @uses add_settings_section
	 * @uses __
	 * @uses this::get_post_types
	 * @uses add_settings_field
	 * @action admin_init
	 * @return null
	 */
	public function action_admin_init() {
		register_setting( $this->settings_page, $this->settings_section, array( $this, 'sanitize_options' ) );

		add_settings_section( $this->settings_section, 'WP Revisions Control', array( $this, 'settings_section_intro' ), $this->settings_page );

		foreach ( $this->get_post_types() as $post_type => $name ) {
			add_settings_field( $this->settings_section . '-' . $post_type, $name, array( $this, 'field_post_type' ), $this->settings_page, $this->settings_section, array( 'post_type' => $post_type ) );
		}
	}

	/**
	 * Display assistive text in settings section
	 *
	 * @uses _e
	 * @uses this::plugin_priority
	 * @return string
	 */
	public function settings_section_intro() {
		?>
		<p><?php _e( 'Set the number of revisions to save for each post type listed. To retain all revisions for a given post type, leave the field empty.', 'wp_revisions_control' ); ?></p>
		<p><?php _e( "If a post type isn't listed, revisions are not enabled for that post type.", 'wp_revisions_control' ); ?></p>
		<?php

		// Display a note if the plugin priority is other than the default.
		// Will be useful when debugging issues later.
		if ( $this->plugin_priority() !== $this->priority_default ) : ?>
			<p><?php _e( "A local change is causing this plugin's functionality to run at a priority other than the default. If you experience difficulties with the plugin, please unhook any functions from the <code>wp_revisions_control_priority</code> filter.", 'wp_revisions_control' ); ?></p>
		<?php endif;
	}

	/**
	 * Render field for each post type
	 *
	 * @param array $args
	 * @uses this::get_revisions_to_keep
	 * @uses esc_attr
	 * @return string
	 */
	public function field_post_type( $args ) {
		$revisions_to_keep = $this->get_revisions_to_keep( $args['post_type'], true );
		?>
		<input type="text" name="<?php echo esc_attr( $this->settings_section . '[' . $args['post_type'] . ']' ); ?>" value="<?php echo esc_attr( $revisions_to_keep ); ?>" class="small-text" />
		<?php
	}

	/**
	 * Sanitize plugin settings
	 *
	 * @param array $options
	 * @return array
	 */
	public function sanitize_options( $options ) {
		$options_sanitized = array();

		if ( is_array( $options ) ) {
			foreach ( $options as $post_type => $to_keep ) {
				if ( 0 === strlen( $to_keep ) )
					$to_keep = -1;
				else
					$to_keep = intval( $to_keep );

				// Lowest possible value is -1, used to indicate infinite revisions are stored
				if ( -1 > $to_keep )
					$to_keep = -1;

				$options_sanitized[ $post_type ] = $to_keep;
			}
		}

		return $options_sanitized;
	}

	/**
	 * Allow others to change the priority this plugin's functionality runs at
	 *
	 * @uses apply_filters
	 * @return int
	 */
	private function plugin_priority() {
		if ( is_null( self::$priority ) ) {
			$plugin_priority = apply_filters( 'wp_revisions_control_priority', $this->priority_default );

			self::$priority = is_numeric( $plugin_priority ) ? (int) $plugin_priority : $this->priority_default;
		}

		return self::$priority;
	}

	/**
	 * Override number of revisions to keep using plugin's settings
	 *
	 * @uses get_post_type
	 * @uses this::get_settings
	 * @filter wp_revisions_to_keep
	 * @return mixed
	 */
	public function filter_wp_revisions_to_keep( $qty, $post ) {
		$post_type = get_post_type( $post ) ? get_post_type( $post ) : $post->post_type;
		$settings = $this->get_settings();

		if ( array_key_exists( $post_type, $settings ) )
			return $settings[ $post_type ];

		return $qty;
	}

	/**
	 * Retrieve plugin settings
	 *
	 * @uses this::get_post_types
	 * @uses get_option
	 * @return array
	 */
	private function get_settings() {
		if ( empty( self::$settings ) ) {
			$post_types = $this->get_post_types();

			$settings = get_option( $this->settings_section, array() );

			$merged_settings = array();

			foreach ( $post_types as $post_type => $name ) {
				if ( array_key_exists( $post_type, $settings ) )
					$merged_settings[ $post_type ] = (int) $settings[ $post_type ];
				else
					$merged_settings[ $post_type ] = -1;
			}

			self::$settings = $merged_settings;
		}

		return self::$settings;
	}

	/**
	 * Retrieve array of supported post types and their labels
	 *
	 * @uses get_post_types
	 * @uses post_type_supports
	 * @uses get_post_type_object
	 * @return array
	 */
	private function get_post_types() {
		if ( empty( self::$post_types ) ) {
			$types = get_post_types();

			foreach ( $types as $type ) {
				if ( post_type_supports( $type, 'revisions' ) ) {
					$object = get_post_type_object( $type );

					if ( property_exists( $object, 'labels' ) && property_exists( $object->labels, 'name' ) )
						$name = $object->labels->name;
					else
						$name = $object->name;

					self::$post_types[ $type ] = $name;
				}
			}
		}

		return self::$post_types;
	}

	/**
	 * Retrieve number of revisions to keep for a given post type
	 *
	 * @uses WP_Post
	 * @uses wp_revisions_to_keep
	 * @return mixed
	 */
	private function get_revisions_to_keep( $post_type, $blank_for_all = false ) {
		// wp_revisions_to_keep() accepts a post object, not just the post type
		// We construct a new WP_Post object to ensure anything hooked to the wp_revisions_to_keep filter has the same basic data WP provides.
		$_post = new WP_Post( (object) array( 'post_type' => $post_type ) );
		$to_keep = wp_revisions_to_keep( $_post );

		if ( $blank_for_all && -1 == $to_keep )
			return '';
		else
			return (int) $to_keep;
	}
}
WP_Revisions_Control::get_instance();
